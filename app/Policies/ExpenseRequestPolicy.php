<?php

namespace App\Policies;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\RoleSlug;
use App\Enums\SettlementStatus;
use App\Models\Attachment;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\User;

class ExpenseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExpenseRequest $expenseRequest): bool
    {
        if ($user->id === $expenseRequest->user_id) {
            return true;
        }

        if ($user->hasExpenseRequestOversight()) {
            return true;
        }

        if ($user->role_id === null) {
            return false;
        }

        return $expenseRequest->approvals()
            ->where('role_id', $user->role_id)
            ->where('status', ApprovalInstanceStatus::Pending)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ExpenseRequest $expenseRequest): bool
    {
        if ($user->id !== $expenseRequest->user_id) {
            return false;
        }

        return $expenseRequest->status === ExpenseRequestStatus::Submitted;
    }

    public function cancel(User $user, ExpenseRequest $expenseRequest): bool
    {
        if ($user->id !== $expenseRequest->user_id) {
            return false;
        }

        return in_array($expenseRequest->status, [
            ExpenseRequestStatus::Submitted,
            ExpenseRequestStatus::ApprovalInProgress,
        ], true);
    }

    public function downloadSubmissionReceipt(User $user, ExpenseRequest $expenseRequest): bool
    {
        return $this->view($user, $expenseRequest);
    }

    public function addSubmissionAttachments(User $user, ExpenseRequest $expenseRequest): bool
    {
        if ($user->id !== $expenseRequest->user_id) {
            return false;
        }

        return $this->submissionAttachmentsWindowOpen($expenseRequest);
    }

    public function deleteSubmissionAttachment(User $user, ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        if ($user->id !== $expenseRequest->user_id) {
            return false;
        }

        if (! $this->submissionAttachmentBelongsToExpenseRequest($expenseRequest, $attachment)) {
            return false;
        }

        return $this->submissionAttachmentsWindowOpen($expenseRequest);
    }

    public function downloadSubmissionAttachment(User $user, ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        return $this->submissionAttachmentBelongsToExpenseRequest($expenseRequest, $attachment);
    }

    private function submissionAttachmentsWindowOpen(ExpenseRequest $expenseRequest): bool
    {
        return in_array($expenseRequest->status, [
            ExpenseRequestStatus::Submitted,
            ExpenseRequestStatus::ApprovalInProgress,
            ExpenseRequestStatus::PendingPayment,
        ], true);
    }

    private function submissionAttachmentBelongsToExpenseRequest(ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        return $attachment->attachable_type === $expenseRequest->getMorphClass()
            && (int) $attachment->attachable_id === (int) $expenseRequest->getKey();
    }

    /**
     * Recibo PDF tras completar la cadena de aprobación (E3 / aprobación final).
     */
    public function downloadFinalApprovalReceipt(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        return $this->hasCompletedExpenseApprovalChain($expenseRequest);
    }

    private function hasCompletedExpenseApprovalChain(ExpenseRequest $expenseRequest): bool
    {
        return in_array($expenseRequest->status, [
            ExpenseRequestStatus::Approved,
            ExpenseRequestStatus::PendingPayment,
            ExpenseRequestStatus::Paid,
            ExpenseRequestStatus::AwaitingExpenseReport,
            ExpenseRequestStatus::ExpenseReportInReview,
            ExpenseRequestStatus::ExpenseReportRejected,
            ExpenseRequestStatus::ExpenseReportApproved,
            ExpenseRequestStatus::SettlementPending,
            ExpenseRequestStatus::Closed,
        ], true);
    }

    public function downloadPaymentReceipt(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $expenseRequest->payments()->exists()) {
            return false;
        }

        return $this->view($user, $expenseRequest);
    }

    public function downloadSettlementLiquidationReceipt(User $user, ExpenseRequest $expenseRequest): bool
    {
        $expenseRequest->loadMissing('expenseReport.settlement');
        $settlement = $expenseRequest->expenseReport?->settlement;
        if ($settlement === null || ! $settlement->attachments()->exists()) {
            return false;
        }

        return $this->view($user, $expenseRequest);
    }

    public function downloadSettlementLiquidationEvidence(User $user, ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        if ($attachment->attachable_type !== (new Settlement)->getMorphClass()) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport.settlement');
        $linkedSettlement = $expenseRequest->expenseReport?->settlement;

        return $linkedSettlement !== null
            && (int) $attachment->attachable_id === (int) $linkedSettlement->getKey();
    }

    /**
     * Authorized when the attachment belongs to a payment of this expense request and the user passes {@see view}
     * (solicitante, roles con oversight como contabilidad, o aprobador con paso pendiente).
     */
    public function downloadPaymentEvidence(User $user, ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        if ($attachment->attachable_type !== (new Payment)->getMorphClass()) {
            return false;
        }

        $paymentBelongs = Payment::query()
            ->where('expense_request_id', $expenseRequest->getKey())
            ->whereKey($attachment->attachable_id)
            ->exists();

        if (! $paymentBelongs) {
            return false;
        }

        return $this->view($user, $expenseRequest);
    }

    /**
     * PDF/XML uploaded as expense report verification (comprobación), once shared beyond draft.
     */
    public function downloadExpenseReportVerificationAttachment(User $user, ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        if ($attachment->attachable_type !== (new ExpenseReport)->getMorphClass()) {
            return false;
        }

        if (! $this->attachmentLooksLikeExpenseReportVerificationFile($attachment)) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport');
        $report = $expenseRequest->expenseReport;

        if ($report === null || (int) $attachment->attachable_id !== (int) $report->getKey()) {
            return false;
        }

        return $this->allowsExpenseReportVerificationFileAccess($user, $expenseRequest, $report);
    }

    /**
     * Acuse PDF when the report is en revisión contable o aprobada (Q5).
     */
    public function downloadExpenseReportVerificationReceipt(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport');
        $report = $expenseRequest->expenseReport;

        if ($report === null) {
            return false;
        }

        return in_array($report->status, [
            ExpenseReportStatus::AccountingReview,
            ExpenseReportStatus::Approved,
        ], true);
    }

    private function allowsExpenseReportVerificationFileAccess(User $user, ExpenseRequest $expenseRequest, ExpenseReport $report): bool
    {
        return match ($report->status) {
            ExpenseReportStatus::Draft => $user->id === $expenseRequest->user_id,
            ExpenseReportStatus::AccountingReview,
            ExpenseReportStatus::Approved,
            ExpenseReportStatus::Rejected => true,
            default => false,
        };
    }

    private function attachmentLooksLikeExpenseReportVerificationFile(Attachment $attachment): bool
    {
        $mime = strtolower((string) $attachment->mime_type);
        $name = strtolower((string) $attachment->original_filename);

        $isPdf = str_contains($mime, 'pdf') || str_ends_with($name, '.pdf');
        $isXml = str_contains($mime, 'xml') || str_ends_with($name, '.xml');

        return $isPdf || $isXml;
    }

    public function recordPayment(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $user->can('create', Payment::class)) {
            return false;
        }

        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        return $expenseRequest->status === ExpenseRequestStatus::PendingPayment
            && ! $expenseRequest->payments()->exists();
    }

    public function saveExpenseReportDraft(User $user, ExpenseRequest $expenseRequest): bool
    {
        if ($user->id !== $expenseRequest->user_id) {
            return false;
        }

        if (! in_array($expenseRequest->status, [
            ExpenseRequestStatus::AwaitingExpenseReport,
            ExpenseRequestStatus::ExpenseReportRejected,
        ], true)) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport');
        $report = $expenseRequest->expenseReport;

        if ($report === null) {
            return true;
        }

        return in_array($report->status, [
            ExpenseReportStatus::Draft,
            ExpenseReportStatus::Rejected,
        ], true);
    }

    public function submitExpenseReport(User $user, ExpenseRequest $expenseRequest): bool
    {
        return $this->saveExpenseReportDraft($user, $expenseRequest);
    }

    public function reviewExpenseReport(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $user->hasRole(RoleSlug::Contabilidad)) {
            return false;
        }

        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport');
        $report = $expenseRequest->expenseReport;

        return $report !== null
            && $report->status === ExpenseReportStatus::AccountingReview
            && $expenseRequest->status === ExpenseRequestStatus::ExpenseReportInReview;
    }

    public function recordSettlementLiquidation(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $user->hasRole(RoleSlug::Contabilidad)) {
            return false;
        }

        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        if ($expenseRequest->status !== ExpenseRequestStatus::SettlementPending) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport.settlement');
        $report = $expenseRequest->expenseReport;
        $settlement = $report?->settlement;

        return $report !== null
            && $report->status === ExpenseReportStatus::Approved
            && $settlement !== null
            && in_array($settlement->status, [
                SettlementStatus::PendingUserReturn,
                SettlementStatus::PendingCompanyPayment,
            ], true);
    }

    public function closeSettlement(User $user, ExpenseRequest $expenseRequest): bool
    {
        if (! $user->hasRole(RoleSlug::Contabilidad)) {
            return false;
        }

        if (! $this->view($user, $expenseRequest)) {
            return false;
        }

        if ($expenseRequest->status !== ExpenseRequestStatus::SettlementPending) {
            return false;
        }

        $expenseRequest->loadMissing('expenseReport.settlement');
        $report = $expenseRequest->expenseReport;
        $settlement = $report?->settlement;

        return $report !== null
            && $report->status === ExpenseReportStatus::Approved
            && $settlement !== null
            && $settlement->status === SettlementStatus::Settled;
    }

    public function delete(User $user, ExpenseRequest $expenseRequest): bool
    {
        return false;
    }

    public function restore(User $user, ExpenseRequest $expenseRequest): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExpenseRequest $expenseRequest): bool
    {
        return false;
    }

    public function approveApproval(User $user, ExpenseRequestApproval $approval): bool
    {
        return $this->allowsActingOnPendingApproval($user, $approval);
    }

    public function rejectApproval(User $user, ExpenseRequestApproval $approval): bool
    {
        return $this->allowsActingOnPendingApproval($user, $approval);
    }

    private function allowsActingOnPendingApproval(User $user, ExpenseRequestApproval $approval): bool
    {
        $expenseRequest = $approval->expenseRequest;

        if ($expenseRequest->status !== ExpenseRequestStatus::ApprovalInProgress) {
            return false;
        }

        if ($approval->status !== ApprovalInstanceStatus::Pending) {
            return false;
        }

        if ($user->role_id === null || $approval->role_id !== $user->role_id) {
            return false;
        }

        if ($user->id === $expenseRequest->user_id) {
            return false;
        }

        return true;
    }
}
