<?php

namespace App\Policies;

use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseReport;
use App\Models\User;

class ExpenseReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('expense_report.view_any');
    }

    public function view(User $user, ExpenseReport $expenseReport): bool
    {
        $expenseReport->loadMissing('expenseRequest');

        if ($user->id === $expenseReport->expenseRequest->user_id) {
            return true;
        }

        return $user->hasPermission('expense_report.view_any');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ExpenseReport $expenseReport): bool
    {
        $expenseReport->loadMissing('expenseRequest');

        if ($user->id !== $expenseReport->expenseRequest->user_id) {
            return false;
        }

        return $expenseReport->status === ExpenseReportStatus::Rejected;
    }

    public function validateReceipt(User $user, ExpenseReport $expenseReport): bool
    {
        if (! $user->hasPermission('expense_report.review')) {
            return false;
        }

        $expenseReport->loadMissing('expenseRequest');
        $expenseRequest = $expenseReport->expenseRequest;

        return $expenseRequest !== null
            && $expenseRequest->status === ExpenseRequestStatus::ExpenseReportInReview
            && $expenseReport->status === ExpenseReportStatus::AccountingReview;
    }

    public function rejectReceipt(User $user, ExpenseReport $expenseReport): bool
    {
        return $this->validateReceipt($user, $expenseReport);
    }

    public function delete(User $user, ExpenseReport $expenseReport): bool
    {
        return false;
    }

    public function restore(User $user, ExpenseReport $expenseReport): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExpenseReport $expenseReport): bool
    {
        return false;
    }
}
