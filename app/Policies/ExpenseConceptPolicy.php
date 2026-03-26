<?php

namespace App\Policies;

use App\Models\ExpenseConcept;
use App\Models\User;

class ExpenseConceptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function view(User $user, ExpenseConcept $expenseConcept): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function create(User $user): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function update(User $user, ExpenseConcept $expenseConcept): bool
    {
        return $user->canManageBudgetsAndPolicies();
    }

    public function delete(User $user, ExpenseConcept $expenseConcept): bool
    {
        if (! $user->canManageBudgetsAndPolicies()) {
            return false;
        }

        return ! $expenseConcept->expenseRequests()->exists();
    }

    public function restore(User $user, ExpenseConcept $expenseConcept): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExpenseConcept $expenseConcept): bool
    {
        return false;
    }
}
