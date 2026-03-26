<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationRequestApproval;

class VacationRequestApprovalPolicy
{
    public function approve(User $user, VacationRequestApproval $approval): bool
    {
        return app(VacationRequestPolicy::class)->approveApproval($user, $approval);
    }

    public function reject(User $user, VacationRequestApproval $approval): bool
    {
        return app(VacationRequestPolicy::class)->rejectApproval($user, $approval);
    }
}
