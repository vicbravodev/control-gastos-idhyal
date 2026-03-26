<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Settlement;
use App\Models\User;

class SettlementPolicy
{
    /**
     * Bandeja de solicitudes con balance en pending_user_return / pending_company_payment.
     */
    public function viewPendingBalances(User $user): bool
    {
        return $user->hasExpenseRequestOversight();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function view(User $user, Settlement $settlement): bool
    {
        $settlement->loadMissing('expenseReport.expenseRequest');

        if ($user->hasRole(RoleSlug::Contabilidad)) {
            return true;
        }

        return $user->id === $settlement->expenseReport->expenseRequest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function update(User $user, Settlement $settlement): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function delete(User $user, Settlement $settlement): bool
    {
        return false;
    }

    public function restore(User $user, Settlement $settlement): bool
    {
        return false;
    }

    public function forceDelete(User $user, Settlement $settlement): bool
    {
        return false;
    }
}
