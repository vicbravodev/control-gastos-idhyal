<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function view(User $user, Budget $budget): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function create(User $user): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function update(User $user, Budget $budget): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $user->canManageBudgetsAndPolicies();
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
