<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function view(User $user, Payment $payment): bool
    {
        $payment->loadMissing('expenseRequest');

        if ($user->hasRole(RoleSlug::Contabilidad)) {
            return true;
        }

        return $user->id === $payment->expenseRequest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasRole(RoleSlug::Contabilidad);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
