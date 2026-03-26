<?php

namespace App\Policies;

use App\Models\ExpenseRequestApproval;
use App\Models\User;

class ExpenseRequestApprovalPolicy
{
    public function approve(User $user, ExpenseRequestApproval $approval): bool
    {
        return app(ExpenseRequestPolicy::class)->approveApproval($user, $approval);
    }

    public function reject(User $user, ExpenseRequestApproval $approval): bool
    {
        return app(ExpenseRequestPolicy::class)->rejectApproval($user, $approval);
    }
}
