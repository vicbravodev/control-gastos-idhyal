<?php

namespace Tests\Feature;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseReportApprovedNotification;
use App\Notifications\ExpenseRequests\ExpenseReportRejectedNotification;
use App\Notifications\ExpenseRequests\ExpenseReportSubmittedForReviewNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseReportHttpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * XML mínimo con forma CFDI 4.0 (SAT) para pruebas HTTP; el Total coincide con centavos MXN declarados.
     */
    private function cfdiXmlForReportedCents(int $reportedAmountCents): string
    {
        $total = number_format($reportedAmountCents / 100, 2, '.', '');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" '
            .'Total="'.$total.'" Moneda="MXN"/>';
    }

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: ExpenseRequest}
     */
    private function awaitingReportSetup(int $approvedCents = 100_000): array
    {
        $requester = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => $approvedCents,
            'approved_amount_cents' => $approvedCents,
            'folio' => 'EXP-COMP-1',
            'concept_description' => 'Comprobación test',
        ]);

        return [$requester, $expenseRequest];
    }

    private function recordPaymentForRequest(ExpenseRequest $expenseRequest, User $accounting, int $amountCents): void
    {
        $file = UploadedFile::fake()->create('pago.pdf', 120, 'application/pdf');
        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => $amountCents,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));
        $expenseRequest->refresh();
    }

    public function test_guest_cannot_view_pending_expense_report_reviews(): void
    {
        $this->get(route('expense-requests.expense-reports.pending-review'))
            ->assertRedirect(route('login'));
    }

    public function test_non_accounting_forbidden_from_pending_review_tray(): void
    {
        $this->seedRoles();
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('expense-requests.expense-reports.pending-review'))
            ->assertForbidden();
    }

    public function test_accounting_can_view_pending_review_tray(): void
    {
        $this->seedRoles();
        Storage::fake('local');
        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(100_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 100_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $this->actingAs($accounting)
            ->get(route('expense-requests.expense-reports.pending-review'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/expense-reports/pending-review')
                ->has('expenseRequests.data', 1)
                ->has('filters')
                ->where('expenseRequests.data.0.id', $expenseRequest->id));
    }

    public function test_requester_can_save_draft_and_submit_notifies_accounting(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.draft', $expenseRequest), [
                'reported_amount_cents' => 95_000,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $report = ExpenseReport::query()->where('expense_request_id', $expenseRequest->id)->firstOrFail();
        $this->assertSame(ExpenseReportStatus::Draft, $report->status);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(95_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 95_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $report->refresh();
        $this->assertSame(ExpenseRequestStatus::ExpenseReportInReview, $expenseRequest->status);
        $this->assertSame(ExpenseReportStatus::AccountingReview, $report->fresh()->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::ExpenseReportSubmitted)
            ->exists());

        Notification::assertSentTo($accounting, ExpenseReportSubmittedForReviewNotification::class);
    }

    public function test_accounting_approves_creates_settlement_and_closes_when_difference_zero(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup(100_000);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(100_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 100_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-report.approve', $expenseRequest), [
                'note' => 'OK',
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::Closed, $expenseRequest->status);

        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame(SettlementStatus::Closed, $settlement->status);
        $this->assertSame(100_000, $settlement->basis_amount_cents);
        $this->assertSame(100_000, $settlement->reported_amount_cents);
        $this->assertSame(0, $settlement->difference_cents);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::ExpenseReportApproved)
            ->exists());

        Notification::assertSentTo($requester, ExpenseReportApprovedNotification::class);
    }

    public function test_accounting_approves_sets_settlement_pending_when_difference_nonzero(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup(100_000);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(80_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 80_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-report.approve', $expenseRequest), [])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::SettlementPending, $expenseRequest->status);

        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame(20_000, $settlement->difference_cents);
        $this->assertSame(SettlementStatus::PendingUserReturn, $settlement->status);
    }

    public function test_accounting_approves_sets_pending_company_payment_when_reported_exceeds_paid(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup(100_000);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(120_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 120_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-report.approve', $expenseRequest), [])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame(-20_000, $settlement->difference_cents);
        $this->assertSame(SettlementStatus::PendingCompanyPayment, $settlement->status);
    }

    public function test_accounting_reject_notifies_requester(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(100_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 100_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-report.reject', $expenseRequest), [
                'note' => 'Falta XML timbrado',
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::ExpenseReportRejected, $expenseRequest->status);
        $this->assertSame(ExpenseReportStatus::Rejected, $expenseRequest->expenseReport->fresh()->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::ExpenseReportRejected)
            ->exists());

        Notification::assertSentTo($requester, ExpenseReportRejectedNotification::class);
    }

    public function test_stranger_cannot_submit_expense_report(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $stranger = User::factory()->forRole('asesor')->create();
        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(100_000));

        $this->actingAs($stranger)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 100_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertForbidden();
    }

    public function test_submit_rejects_non_cfdi_xml(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', '<?xml version="1.0"?><Factura/>');

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 100_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }

    public function test_submit_rejects_when_xml_total_does_not_match_reported_amount(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents(100_000));

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 95_000,
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }

    public function test_draft_save_rejects_invalid_cfdi_xml(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->awaitingReportSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest->update(['status' => ExpenseRequestStatus::PendingPayment]);
        $this->recordPaymentForRequest($expenseRequest, $accounting, 100_000);

        $xml = UploadedFile::fake()->createWithContent('c.xml', '<?xml version="1.0"?><root/>');

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.draft', $expenseRequest), [
                'reported_amount_cents' => 95_000,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }
}
