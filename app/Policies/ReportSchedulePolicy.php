<?php

namespace App\Policies;

use App\Models\ReportSchedule;
use App\Models\User;

class ReportSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('report.expenses.view');
    }

    public function view(User $user, ReportSchedule $schedule): bool
    {
        return $schedule->owner_user_id === $user->id
            || $user->hasPermission('admin.roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('report.expenses.view');
    }

    public function update(User $user, ReportSchedule $schedule): bool
    {
        return $schedule->owner_user_id === $user->id
            || $user->hasPermission('admin.roles.manage');
    }

    public function delete(User $user, ReportSchedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
