<?php

namespace Tests\Feature;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Models\CfdiTraslado;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseRequestPaidNotification;
use App\Services\ExpenseRequests\ExpenseRequestPaymentReceiptPdf;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseRequestPaymentHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: ExpenseRequest}
     */
    private function pendingPaymentSetup(): array
    {
        $requester = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'requested_amount_cents' => 100_000,
            'approved_amount_cents' => 100_000,
            'folio' => 'EXP-TEST-99',
            'concept_description' => 'Prueba de pago',
        ]);

        return [$requester, $expenseRequest];
    }

    public function test_guest_cannot_view_pending_payments_tray(): void
    {
        $this->get(route('expense-requests.payments.pending'))
            ->assertRedirect(route('login'));
    }

    public function test_non_accounting_user_forbidden_from_pending_payments_tray(): void
    {
        $this->seedRoles();
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('expense-requests.payments.pending'))
            ->assertForbidden();
    }

    public function test_accounting_user_can_view_pending_payments_tray(): void
    {
        $this->seedRoles();
        [, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($accounting)
            ->get(route('expense-requests.payments.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/payments/pending')
                ->has('expenseRequests.data', 1)
                ->has('filters')
                ->where('expenseRequests.data.0.id', $expenseRequest->id));
    }

    public function test_pending_payments_tray_search_filters_by_folio_or_requester_name(): void
    {
        $this->seedRoles();
        $requesterA = User::factory()->create(['name' => 'Unique Payer Alpha']);
        $requesterB = User::factory()->create(['name' => 'Other Person Beta']);
        $match = ExpenseRequest::factory()->create([
            'user_id' => $requesterA->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'folio' => 'EXP-PAY-FILTER-1',
        ]);
        ExpenseRequest::factory()->create([
            'user_id' => $requesterB->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'folio' => 'EXP-PAY-OTHER-2',
        ]);
        $accounting = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($accounting)
            ->get(route('expense-requests.payments.pending', ['search' => 'FILTER-1']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseRequests.data', 1)
                ->where('expenseRequests.data.0.id', $match->id)
                ->where('filters.search', 'FILTER-1'));

        $this->actingAs($accounting)
            ->get(route('expense-requests.payments.pending', ['search' => 'Unique Payer']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseRequests.data', 1)
                ->where('expenseRequests.data.0.id', $match->id));
    }

    public function test_accounting_can_record_payment_and_notifies_requester(): void
    {
        $this->seedRoles();
        Storage::fake('local');
        Notification::fake();

        [$requester, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();

        $file = UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'transfer',
                'paid_on' => '2026-03-20',
                'transfer_reference' => 'TRF-001',
                'evidence' => $file,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::AwaitingExpenseReport, $expenseRequest->status);

        $this->assertSame(1, Payment::query()->where('expense_request_id', $expenseRequest->id)->count());
        $payment = Payment::query()->where('expense_request_id', $expenseRequest->id)->firstOrFail();
        $this->assertSame(100_000, $payment->amount_cents);
        $this->assertSame($accounting->id, $payment->recorded_by_user_id);

        $this->assertSame(1, $payment->attachments()->count());
        $attachment = $payment->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::ExpenseRequestPaid)
            ->exists());

        Notification::assertSentTo($requester, ExpenseRequestPaidNotification::class);
    }

    public function test_transfer_payment_does_not_require_reference(): void
    {
        $this->seedRoles();
        Storage::fake('local');
        Notification::fake();

        [, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $file = UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'transfer',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertSessionHasNoErrors();

        $payment = Payment::query()->where('expense_request_id', $expenseRequest->id)->firstOrFail();
        $this->assertNull($payment->transfer_reference);
    }

    public function test_requester_cannot_record_payment(): void
    {
        $this->seedRoles();
        [$requester, $expenseRequest] = $this->pendingPaymentSetup();
        $file = UploadedFile::fake()->create('x.pdf', 50, 'application/pdf');

        $this->actingAs($requester)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertForbidden();
    }

    public function test_cannot_record_payment_twice(): void
    {
        $this->seedRoles();
        Storage::fake('local');
        Notification::fake();

        [, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $file = UploadedFile::fake()->create('c.pdf', 50, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertSessionHasNoErrors();

        $file2 = UploadedFile::fake()->create('c2.pdf', 50, 'application/pdf');

        $this->actingAs($accounting)
            ->from(route('expense-requests.show', $expenseRequest))
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-21',
                'evidence' => $file2,
            ])
            ->assertForbidden();
    }

    public function test_payment_amount_must_match_approved(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $file = UploadedFile::fake()->create('c.pdf', 50, 'application/pdf');

        $this->actingAs($accounting)
            ->from(route('expense-requests.show', $expenseRequest))
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 99_999,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertSessionHasErrors('amount_cents');
    }

    public function test_requester_and_accounting_can_download_payment_receipt_pdf(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $file = UploadedFile::fake()->create('c.pdf', 50, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'cash',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.payment', $expenseRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($accounting)
            ->get(route('expense-requests.receipts.payment', $expenseRequest))
            ->assertOk();
    }

    public function test_payment_receipt_includes_cfdi_fiscal_summary(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->pendingPaymentSetup();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $file = UploadedFile::fake()->create('c.pdf', 50, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.payments.store', $expenseRequest), [
                'amount_cents' => 100_000,
                'payment_method' => 'transfer',
                'paid_on' => '2026-03-20',
                'evidence' => $file,
            ])
            ->assertSessionHasNoErrors();

        $report = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'reported_amount_cents' => 116_000,
            'cfdi_uuid' => 'F1B2A3D4-5E6F-7A8B-9C0D-1E2F3A4B5C6D',
            'cfdi_serie' => 'A',
            'cfdi_folio' => '42',
            'cfdi_emisor_rfc' => 'STK230120D23',
            'cfdi_emisor_nombre' => 'PROVEEDOR DEMO',
            'cfdi_fecha' => '2026-03-15 10:00:00',
            'cfdi_conceptos' => [['descripcion' => 'Servicio', 'importe' => 1000.00]],
        ]);

        CfdiTraslado::create([
            'expense_report_id' => $report->id,
            'impuesto' => '002',
            'impuesto_label' => 'IVA',
            'tipo_factor' => 'Tasa',
            'tasa_o_cuota' => 0.16,
            'base_cents' => 100_000,
            'importe_cents' => 16_000,
            'nivel' => 'document',
            'concepto_index' => null,
        ]);

        $expenseRequest->load([
            'expenseReports' => fn ($q) => $q->whereNotNull('cfdi_uuid'),
            'expenseReports.cfdiTraslados',
            'expenseReports.cfdiRetenciones',
            'expenseReports.cfdiImpuestosLocales',
        ]);

        $summaries = ExpenseRequestPaymentReceiptPdf::buildCfdiSummaries($expenseRequest->expenseReports);
        $this->assertCount(1, $summaries);
        $this->assertSame(100_000, $summaries[0]['subtotal_cents']);
        $this->assertSame(16_000, $summaries[0]['traslados_cents']);
        $this->assertSame(0, $summaries[0]['retenciones_cents']);
        $this->assertSame(116_000, $summaries[0]['total_cents']);

        $html = view('pdf.expense-request-payment', [
            'expenseRequest' => $expenseRequest,
            'payment' => $expenseRequest->payments()->first(),
            'cfdiSummaries' => $summaries,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Comprobantes fiscales (CFDI)', $html);
        $this->assertStringContainsString('PROVEEDOR DEMO', $html);
        $this->assertStringContainsString('1,000.00', $html);
        $this->assertStringContainsString('160.00', $html);
        $this->assertStringContainsString('1,160.00', $html);

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.payment', $expenseRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_download_payment_receipt_without_payment(): void
    {
        $this->seedRoles();
        [$requester, $expenseRequest] = $this->pendingPaymentSetup();

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.payment', $expenseRequest))
            ->assertForbidden();
    }
}
