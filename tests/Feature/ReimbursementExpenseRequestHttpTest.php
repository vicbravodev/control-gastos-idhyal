<?php

namespace Tests\Feature;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\DocumentEvent;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReimbursementExpenseRequestHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function cfdiXmlForReportedCents(int $reportedAmountCents): string
    {
        $total = number_format($reportedAmountCents / 100, 2, '.', '');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" '
            .'Total="'.$total.'" Moneda="MXN"/>';
    }

    private function expenseConcept(): ExpenseConcept
    {
        return ExpenseConcept::factory()->create();
    }

    public function test_reimbursement_endpoint_creates_synthetic_request_in_review(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $concept = $this->expenseConcept();

        $pdf = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', $this->cfdiXmlForReportedCents(50_000));

        $response = $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Compra de papelería',
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $expenseRequest = ExpenseRequest::query()
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $response->assertRedirect(route('expense-requests.show', $expenseRequest));

        $this->assertTrue($expenseRequest->is_reimbursement);
        $this->assertSame(ExpenseRequestStatus::ExpenseReportInReview, $expenseRequest->status);
        $this->assertSame(50_000, $expenseRequest->approved_amount_cents);

        $report = $expenseRequest->expenseReport;
        $this->assertNotNull($report);
        $this->assertSame(ExpenseReportStatus::AccountingReview, $report->status);
        $this->assertSame(50_000, $report->reported_amount_cents);
        $this->assertSame(ExpenseReportDocumentType::Factura, $report->document_type);

        $this->assertTrue(
            DocumentEvent::query()
                ->where('subject_id', $expenseRequest->id)
                ->where('event_type', DocumentEventType::ExpenseRequestReimbursementCreated)
                ->exists(),
        );
    }

    public function test_reimbursement_recibo_only_requires_pdf(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $concept = $this->expenseConcept();

        $pdf = UploadedFile::fake()->create('recibo.pdf', 100, 'application/pdf');

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 25_000,
                'expense_concept_id' => $concept->id,
                'document_type' => 'recibo',
                'pdf' => $pdf,
            ])
            ->assertRedirect();

        $expenseRequest = ExpenseRequest::query()
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $this->assertSame(ExpenseReportDocumentType::Recibo, $expenseRequest->expenseReport->document_type);
    }

    public function test_reimbursement_factura_without_xml_is_rejected(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $concept = $this->expenseConcept();
        $pdf = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'document_type' => 'factura',
                'pdf' => $pdf,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('xml');

        $this->assertSame(0, ExpenseRequest::query()->count());
    }

    public function test_accounting_can_approve_reimbursement_creating_pending_company_payment(): void
    {
        Storage::fake('local');
        $employee = User::factory()->forRole('asesor')->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $concept = $this->expenseConcept();

        $pdf = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', $this->cfdiXmlForReportedCents(75_000));

        $this->actingAs($employee)
            ->post(route('expense-requests.reimbursements.store'), [
                'reported_amount_cents' => 75_000,
                'expense_concept_id' => $concept->id,
                'document_type' => 'factura',
                'pdf' => $pdf,
                'xml' => $xml,
            ]);

        $expenseRequest = ExpenseRequest::query()
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-report.approve', $expenseRequest), [])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::SettlementPending, $expenseRequest->status);

        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame(0, $settlement->basis_amount_cents);
        $this->assertSame(75_000, $settlement->reported_amount_cents);
        $this->assertSame(-75_000, $settlement->difference_cents);
        $this->assertSame(SettlementStatus::PendingCompanyPayment, $settlement->status);
    }
}
