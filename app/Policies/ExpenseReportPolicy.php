<?php

namespace App\Policies;

use App\Enums\ExpenseReportStatus;
use App\Enums\RoleSlug;
use App\Models\ExpenseReport;
use App\Models\User;

class ExpenseReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function view(User $user, ExpenseReport $expenseReport): bool
    {
        $expenseReport->loadMissing('expenseRequest');

        if ($user->id === $expenseReport->expenseRequest->user_id) {
            return true;
        }

        return $user->hasRole(RoleSlug::Contabilidad);
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

        return in_array($expenseReport->status, [
            ExpenseReportStatus::Draft,
            ExpenseReportStatus::Rejected,
        ], true);
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
