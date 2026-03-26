<?php

namespace Tests\Feature;

use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\Attachment;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseReportVerificationHttpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: ExpenseRequest, 3: ExpenseReport, 4: Attachment, 5: Attachment}
     */
    private function expenseRequestWithVerificationAttachments(ExpenseReportStatus $reportStatus): array
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();

        $expenseRequestStatus = match ($reportStatus) {
            ExpenseReportStatus::Draft => ExpenseRequestStatus::AwaitingExpenseReport,
            ExpenseReportStatus::AccountingReview => ExpenseRequestStatus::ExpenseReportInReview,
            ExpenseReportStatus::Rejected => ExpenseRequestStatus::ExpenseReportRejected,
            ExpenseReportStatus::Approved => ExpenseRequestStatus::SettlementPending,
            default => ExpenseRequestStatus::ExpenseReportInReview,
        };

        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => $expenseRequestStatus,
            'approved_amount_cents' => 50_000,
        ]);

        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 50_000,
        ]);

        $report = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => $reportStatus,
            'reported_amount_cents' => 48_000,
            'submitted_at' => $reportStatus === ExpenseReportStatus::Draft ? null : now(),
        ]);

        if ($reportStatus === ExpenseReportStatus::Approved) {
            Settlement::query()->create([
                'expense_report_id' => $report->id,
                'status' => SettlementStatus::PendingUserReturn,
                'basis_amount_cents' => 50_000,
                'reported_amount_cents' => 48_000,
                'difference_cents' => 2_000,
            ]);
        }

        Storage::disk('local')->put('expense-reports/'.$report->id.'/pdf/doc.pdf', 'pdf-bytes');
        $pdfAttachment = Attachment::query()->create([
            'attachable_type' => $report->getMorphClass(),
            'attachable_id' => $report->id,
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => 'expense-reports/'.$report->id.'/pdf/doc.pdf',
            'original_filename' => 'factura.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
        ]);

        Storage::disk('local')->put('expense-reports/'.$report->id.'/xml/doc.xml', '<xml/>');
        $xmlAttachment = Attachment::query()->create([
            'attachable_type' => $report->getMorphClass(),
            'attachable_id' => $report->id,
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => 'expense-reports/'.$report->id.'/xml/doc.xml',
            'original_filename' => 'cfdi.xml',
            'mime_type' => 'application/xml',
            'size_bytes' => 6,
        ]);

        return [$owner, $accounting, $expenseRequest, $report, $pdfAttachment, $xmlAttachment];
    }

    public function test_owner_and_accounting_can_download_verification_pdf_and_xml_when_in_review(): void
    {
        [$owner, $accounting, $expenseRequest, , $pdfAttachment, $xmlAttachment] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::AccountingReview);

        $this->actingAs($owner)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequest,
                'attachment' => $pdfAttachment,
            ]))
            ->assertOk()
            ->assertDownload('factura.pdf');

        $this->actingAs($accounting)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequest,
                'attachment' => $xmlAttachment,
            ]))
            ->assertOk()
            ->assertDownload('cfdi.xml');
    }

    public function test_stranger_cannot_download_verification_attachment(): void
    {
        [, , $expenseRequest, , $pdfAttachment] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::AccountingReview);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequest,
                'attachment' => $pdfAttachment,
            ]))
            ->assertForbidden();
    }

    public function test_cannot_download_foreign_verification_attachment(): void
    {
        [$owner, , $expenseRequestA, , $pdfAttachmentA] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::AccountingReview);

        $ownerB = User::factory()->create();
        $expenseRequestB = ExpenseRequest::factory()->create([
            'user_id' => $ownerB->id,
            'status' => ExpenseRequestStatus::ExpenseReportInReview,
        ]);

        $this->actingAs($owner)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequestB,
                'attachment' => $pdfAttachmentA,
            ]))
            ->assertForbidden();
    }

    public function test_accounting_cannot_download_draft_verification_files_even_when_viewing_request(): void
    {
        [, $accounting, $expenseRequest, , $pdfAttachment] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::Draft);

        $this->actingAs($accounting)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequest,
                'attachment' => $pdfAttachment,
            ]))
            ->assertForbidden();
    }

    public function test_owner_can_download_draft_verification_pdf(): void
    {
        [$owner, , $expenseRequest, , $pdfAttachment] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::Draft);

        $this->actingAs($owner)
            ->get(route('expense-requests.expense-reports.verification-attachment', [
                'expense_request' => $expenseRequest,
                'attachment' => $pdfAttachment,
            ]))
            ->assertOk();
    }

    public function test_receipt_pdf_available_when_accounting_review(): void
    {
        [$owner, , $expenseRequestInReview] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::AccountingReview);

        $this->actingAs($owner)
            ->get(route('expense-requests.receipts.expense-report-verification', [
                'expense_request' => $expenseRequestInReview,
            ]))
            ->assertOk();
    }

    public function test_receipt_pdf_available_when_report_approved(): void
    {
        [, $accounting, $expenseRequestApproved] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::Approved);

        $this->actingAs($accounting)
            ->get(route('expense-requests.receipts.expense-report-verification', [
                'expense_request' => $expenseRequestApproved,
            ]))
            ->assertOk();
    }

    public function test_receipt_pdf_forbidden_when_report_is_draft(): void
    {
        [$owner, , $expenseRequest] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::Draft);

        $this->actingAs($owner)
            ->get(route('expense-requests.receipts.expense-report-verification', [
                'expense_request' => $expenseRequest,
            ]))
            ->assertForbidden();
    }

    public function test_receipt_pdf_forbidden_when_report_is_rejected(): void
    {
        [$owner, , $expenseRequest] = $this->expenseRequestWithVerificationAttachments(ExpenseReportStatus::Rejected);

        $this->actingAs($owner)
            ->get(route('expense-requests.receipts.expense-report-verification', [
                'expense_request' => $expenseRequest,
            ]))
            ->assertForbidden();
    }
}
