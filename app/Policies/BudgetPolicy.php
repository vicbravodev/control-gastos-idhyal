<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['budget.view_any', 'budget.manage']);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $user->hasAnyPermission(['budget.view_any', 'budget.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('budget.manage');
    }

    public function update(User $user, Budget $budget): bool
    {
        return $budget->isActive() && $user->hasPermission('budget.manage');
    }

    public function cancel(User $user, Budget $budget): bool
    {
        return $budget->isActive() && $user->hasPermission('budget.manage');
    }

    public function delete(User $user, Budget $budget): bool
    {
        return false;
    }

    public function restore(User $user, Budget $budget): bool
    {
        return false;
    }

    public function forceDelete(User $user, Budget $budget): bool
    {
        return false;
    }
}
