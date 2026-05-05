<?php

namespace Tests\Feature;

use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CfdiTestFixtures;
use Tests\TestCase;

class CfdiIngestionHttpTest extends TestCase
{
    use CfdiTestFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: User, 2: ExpenseRequest}
     */
    private function pendingReportSetup(int $approvedCents = 100_000): array
    {
        $requester = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => $approvedCents,
            'approved_amount_cents' => $approvedCents,
            'folio' => 'EXP-CFDI-1',
        ]);
        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => $approvedCents,
        ]);

        return [$requester, $accounting, $expenseRequest];
    }

    public function test_submit_auto_fills_amount_from_xml_when_zero(): void
    {
        Storage::fake('local');
        [$requester, , $expenseRequest] = $this->pendingReportSetup(50_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(50_000),
        );

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 0,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $report = $expenseRequest->fresh()->expenseReport;
        $this->assertSame(50_000, $report->reported_amount_cents);
    }

    public function test_submit_persists_cfdi_metadata(): void
    {
        Storage::fake('local');
        [$requester, , $expenseRequest] = $this->pendingReportSetup(75_000);
        $uuid = '51DA9325-6DF1-4064-AAAB-386F7BC84AD6';

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(75_000, uuid: $uuid),
        );

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 75_000,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $report = $expenseRequest->fresh()->expenseReport;
        $this->assertSame($uuid, $report->cfdi_uuid);
        $this->assertSame('STK230120D23', $report->cfdi_emisor_rfc);
        $this->assertSame('IDH800514B86', $report->cfdi_receptor_rfc);
        $this->assertSame('A', $report->cfdi_serie);
        $this->assertSame('42', $report->cfdi_folio);
        $this->assertSame('PUE', $report->cfdi_metodo_pago);
        $this->assertNotNull($report->cfdi_fecha);
        $this->assertNotEmpty($report->cfdi_conceptos);
        $this->assertSame('Servicio de prueba', $report->cfdi_conceptos[0]['descripcion']);
    }

    public function test_submit_rejects_duplicate_cfdi_uuid_across_users(): void
    {
        Storage::fake('local');
        [$requesterA, , $expenseRequestA] = $this->pendingReportSetup(50_000);
        $expenseRequestA->update(['folio' => 'EXP-CFDI-A']);

        $uuid = '00000000-0000-4000-A000-000000000001';
        $xml1 = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(50_000, uuid: $uuid),
        );
        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');

        $this->actingAs($requesterA)
            ->post(route('expense-requests.expense-report.submit', $expenseRequestA), [
                'reported_amount_cents' => 50_000,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml1,
            ])
            ->assertRedirect();

        $requesterB = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequestB = ExpenseRequest::factory()->create([
            'user_id' => $requesterB->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => 50_000,
            'approved_amount_cents' => 50_000,
            'folio' => 'EXP-CFDI-B',
        ]);
        Payment::factory()->create([
            'expense_request_id' => $expenseRequestB->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 50_000,
        ]);

        $xml2 = UploadedFile::fake()->createWithContent(
            'dup.xml',
            $this->cfdiXmlForReportedCents(50_000, uuid: $uuid),
        );
        $pdf2 = UploadedFile::fake()->create('dup.pdf', 100, 'application/pdf');

        $this->actingAs($requesterB)
            ->post(route('expense-requests.expense-report.submit', $expenseRequestB), [
                'reported_amount_cents' => 50_000,
                'document_type' => 'factura',
                'pdf' => $pdf2,
                'xml' => $xml2,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');

        $this->assertNull($expenseRequestB->fresh()->expenseReport);
    }

    public function test_submit_validates_receptor_rfc_when_configured(): void
    {
        config()->set('expense_reports.cfdi.expected_receiver_rfc', 'IDH800514B86');

        Storage::fake('local');
        [$requester, , $expenseRequest] = $this->pendingReportSetup(50_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(50_000, receptorRfc: 'XAXX010101000'),
        );

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 50_000,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }

    public function test_submit_rejects_factura_too_old(): void
    {
        config()->set('expense_reports.cfdi.max_age_days', 30);

        Storage::fake('local');
        [$requester, , $expenseRequest] = $this->pendingReportSetup(50_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(
                50_000,
                fecha: now()->subDays(60)->toIso8601String(),
            ),
        );

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 50_000,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }

    public function test_submit_rejects_factura_in_the_future(): void
    {
        Storage::fake('local');
        [$requester, , $expenseRequest] = $this->pendingReportSetup(50_000);

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(
                50_000,
                fecha: now()->addDays(10)->toIso8601String(),
            ),
        );

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-report.submit', $expenseRequest), [
                'reported_amount_cents' => 50_000,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');
    }

    public function test_reimbursement_auto_fills_amount_from_xml_when_zero(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $concept = ExpenseConcept::factory()->create();

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(120_000),
        );

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 0,
                'expense_concept_id' => $concept->id,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect();

        $request = ExpenseRequest::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame(120_000, $request->approved_amount_cents);
        $this->assertSame(120_000, $request->expenseReport->reported_amount_cents);
        $this->assertSame(ExpenseReportStatus::AccountingReview, $request->expenseReport->status);
    }

    public function test_reimbursement_rejects_duplicate_uuid_against_existing_report(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $concept = ExpenseConcept::factory()->create();
        $uuid = '00000000-0000-4000-A000-000000000099';

        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent(
            'c.xml',
            $this->cfdiXmlForReportedCents(50_000, uuid: $uuid),
        );

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ])
            ->assertRedirect();

        $pdf2 = UploadedFile::fake()->create('dup.pdf', 100, 'application/pdf');
        $xml2 = UploadedFile::fake()->createWithContent(
            'dup.xml',
            $this->cfdiXmlForReportedCents(50_000, uuid: $uuid),
        );

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'document_type' => 'factura',
                'pdf' => $pdf2,
                'xml' => $xml2,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');

        $this->assertSame(1, ExpenseRequest::query()->where('user_id', $employee->id)->count());
    }
}
