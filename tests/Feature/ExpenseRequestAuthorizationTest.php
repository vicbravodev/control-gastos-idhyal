<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\Attachment;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseRequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_own_expense_request(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($owner->can('view', $expenseRequest));
    }

    public function test_stranger_cannot_view_others_expense_request(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $expenseRequest));
    }

    public function test_super_admin_can_view_any_expense_request(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();
        $expenseRequest = ExpenseRequest::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($admin->can('view', $expenseRequest));
    }

    public function test_contabilidad_can_view_others_expense_request(): void
    {
        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($accounting->can('view', $expenseRequest));
    }

    public function test_user_with_pending_approval_for_role_can_view_expense_request(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->forRole('asesor')->create();
        $approver = User::factory()->forRole('coord_regional')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        ExpenseRequestApproval::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'step_order' => 1,
            'role_id' => $approver->role_id,
            'status' => ApprovalInstanceStatus::Pending,
        ]);

        $this->assertTrue($approver->can('view', $expenseRequest));
    }

    public function test_owner_cannot_update_terminal_expense_request(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Rejected,
        ]);

        $this->assertFalse($owner->can('update', $expenseRequest));
    }

    public function test_owner_can_update_non_terminal_expense_request(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->assertTrue($owner->can('update', $expenseRequest));
    }

    public function test_owner_cannot_update_when_approval_in_progress(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertFalse($owner->can('update', $expenseRequest));
    }

    public function test_owner_cannot_update_when_pending_payment(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
        ]);

        $this->assertFalse($owner->can('update', $expenseRequest));
    }

    public function test_owner_can_cancel_when_submitted_or_approval_in_progress(): void
    {
        $owner = User::factory()->create();
        $submitted = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);
        $inProgress = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertTrue($owner->can('cancel', $submitted));
        $this->assertTrue($owner->can('cancel', $inProgress));
    }

    public function test_owner_cannot_cancel_when_pending_payment(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
        ]);

        $this->assertFalse($owner->can('cancel', $expenseRequest));
    }

    public function test_stranger_cannot_cancel_others_expense_request(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertFalse($stranger->can('cancel', $expenseRequest));
    }

    public function test_contabilidad_can_record_payment_when_pending_and_no_payment_yet(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 50_000,
        ]);

        $this->assertTrue($accounting->can('recordPayment', $expenseRequest));
    }

    public function test_owner_cannot_record_payment(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 50_000,
        ]);

        $this->assertFalse($owner->can('recordPayment', $expenseRequest));
    }

    public function test_download_final_approval_receipt_when_pending_payment(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 40_000,
        ]);

        $this->assertTrue($owner->can('downloadFinalApprovalReceipt', $expenseRequest));
    }

    public function test_download_final_approval_receipt_forbidden_during_approval_in_progress(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertFalse($owner->can('downloadFinalApprovalReceipt', $expenseRequest));
    }

    public function test_download_final_approval_receipt_forbidden_for_stranger(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
        ]);

        $this->assertFalse($stranger->can('downloadFinalApprovalReceipt', $expenseRequest));
    }

    public function test_owner_can_add_submission_attachments_during_approval(): void
    {
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertTrue($owner->can('addSubmissionAttachments', $expenseRequest));
    }

    public function test_non_owner_cannot_add_submission_attachments(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->forRole('asesor')->create();
        $other = User::factory()->forRole('asesor')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->assertFalse($other->can('addSubmissionAttachments', $expenseRequest));
    }

    public function test_download_submission_attachment_requires_view(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->forRole('asesor')->create();
        $stranger = User::factory()->forRole('asesor')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);
        $attachment = Attachment::query()->create([
            'attachable_type' => $expenseRequest->getMorphClass(),
            'attachable_id' => $expenseRequest->id,
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => 'x/y.pdf',
            'original_filename' => 'y.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1,
        ]);

        $this->assertTrue(
            $owner->can('downloadSubmissionAttachment', [$expenseRequest, $attachment]),
        );
        $this->assertFalse(
            $stranger->can('downloadSubmissionAttachment', [$expenseRequest, $attachment]),
        );
    }

    public function test_download_payment_receipt_requires_payment_row(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
        ]);

        $this->assertFalse($owner->can('downloadPaymentReceipt', $expenseRequest));

        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => User::factory()->forRole('contabilidad')->create()->id,
            'amount_cents' => 50_000,
        ]);

        $this->assertTrue($owner->can('downloadPaymentReceipt', $expenseRequest));
    }

    public function test_download_settlement_liquidation_receipt_requires_liquidation_attachment(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::SettlementPending,
            'approved_amount_cents' => 100_000,
        ]);
        $report = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 80_000,
            'submitted_at' => now(),
        ]);
        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::PendingUserReturn,
            'basis_amount_cents' => 100_000,
            'reported_amount_cents' => 80_000,
            'difference_cents' => 20_000,
        ]);

        $this->assertFalse($owner->can('downloadSettlementLiquidationReceipt', $expenseRequest->fresh()));

        $settlement = Settlement::query()->firstOrFail();
        Attachment::query()->create([
            'attachable_type' => $settlement->getMorphClass(),
            'attachable_id' => $settlement->id,
            'uploaded_by_user_id' => $accounting->id,
            'disk' => 'local',
            'path' => 'settlements/'.$settlement->id.'/fake.pdf',
            'original_filename' => 'evidencia.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $this->assertTrue($owner->can('downloadSettlementLiquidationReceipt', $expenseRequest->fresh()));
    }

    public function test_download_payment_evidence_requires_payment_attachment_for_expense_request(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'approved_amount_cents' => 50_000,
        ]);

        $payment = Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 50_000,
        ]);

        $attachment = Attachment::query()->create([
            'attachable_type' => $payment->getMorphClass(),
            'attachable_id' => $payment->id,
            'uploaded_by_user_id' => $accounting->id,
            'disk' => 'local',
            'path' => 'payments/'.$payment->id.'/x.pdf',
            'original_filename' => 'x.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1,
        ]);

        $this->assertTrue($owner->can('downloadPaymentEvidence', [$expenseRequest, $attachment]));
        $this->assertTrue($accounting->can('downloadPaymentEvidence', [$expenseRequest, $attachment]));

        $settlement = Settlement::query()->create([
            'expense_report_id' => ExpenseReport::factory()->create([
                'expense_request_id' => $expenseRequest->id,
                'status' => ExpenseReportStatus::Approved,
                'reported_amount_cents' => 50_000,
                'submitted_at' => now(),
            ])->id,
            'status' => SettlementStatus::PendingUserReturn,
            'basis_amount_cents' => 50_000,
            'reported_amount_cents' => 50_000,
            'difference_cents' => 0,
        ]);

        $foreignAttachment = Attachment::query()->create([
            'attachable_type' => $settlement->getMorphClass(),
            'attachable_id' => $settlement->id,
            'uploaded_by_user_id' => $accounting->id,
            'disk' => 'local',
            'path' => 'settlements/'.$settlement->id.'/y.pdf',
            'original_filename' => 'y.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1,
        ]);

        $this->assertFalse($owner->can('downloadPaymentEvidence', [$expenseRequest, $foreignAttachment]));
    }

    public function test_super_admin_cannot_record_payment_in_wrong_status(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();

        foreach ([ExpenseRequestStatus::SettlementPending, ExpenseRequestStatus::Paid, ExpenseRequestStatus::Closed] as $status) {
            $expenseRequest = ExpenseRequest::factory()->create([
                'user_id' => $owner->id,
                'status' => $status,
                'approved_amount_cents' => 50_000,
            ]);
            $this->assertFalse($admin->can('recordPayment', $expenseRequest), "SuperAdmin should not record payment when status is {$status->value}");
        }
    }

    public function test_super_admin_cannot_cancel_in_wrong_status(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();

        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::SettlementPending,
        ]);

        $this->assertFalse($admin->can('cancel', $expenseRequest));
    }

    public function test_super_admin_cannot_review_expense_report_in_wrong_status(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::SettlementPending,
        ]);

        $this->assertFalse($admin->can('reviewExpenseReport', $expenseRequest));
    }

    public function test_super_admin_can_download_final_approval_receipt(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 40_000,
        ]);

        $this->assertTrue($admin->can('downloadFinalApprovalReceipt', $expenseRequest));
    }

    public function test_super_admin_can_download_submission_receipt(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->forRole('super_admin')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->assertTrue($admin->can('downloadSubmissionReceipt', $expenseRequest));
    }

    public function test_download_expense_report_verification_receipt_only_when_submitted_or_approved(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
        ]);
        $reportDraft = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => ExpenseReportStatus::Draft,
        ]);

        $this->assertFalse($owner->can('downloadExpenseReportVerificationReceipt', $expenseRequest));

        $reportDraft->delete();
        ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => ExpenseReportStatus::AccountingReview,
            'submitted_at' => now(),
        ]);
        $expenseRequest->update(['status' => ExpenseRequestStatus::ExpenseReportInReview]);

        $this->assertTrue($owner->can('downloadExpenseReportVerificationReceipt', $expenseRequest->fresh()));
        $this->assertTrue($accounting->can('downloadExpenseReportVerificationReceipt', $expenseRequest->fresh()));
    }
}
