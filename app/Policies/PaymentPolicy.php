<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payment.view_any');
    }

    public function view(User $user, Payment $payment): bool
    {
        $payment->loadMissing('expenseRequest');

        if ($user->hasPermission('payment.view_any')) {
            return true;
        }

        return $user->id === $payment->expenseRequest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payment.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payment.update');
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
