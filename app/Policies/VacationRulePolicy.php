<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationRule;

class VacationRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vacation_rule.manage');
    }

    public function view(User $user, VacationRule $vacationRule): bool
    {
        return $user->hasPermission('vacation_rule.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vacation_rule.manage');
    }

    public function update(User $user, VacationRule $vacationRule): bool
    {
        return $user->hasPermission('vacation_rule.manage');
    }

    public function delete(User $user, VacationRule $vacationRule): bool
    {
        return $user->hasPermission('vacation_rule.manage');
    }

    public function restore(User $user, VacationRule $vacationRule): bool
    {
        return false;
    }

    public function forceDelete(User $user, VacationRule $vacationRule): bool
    {
        return false;
    }
}
