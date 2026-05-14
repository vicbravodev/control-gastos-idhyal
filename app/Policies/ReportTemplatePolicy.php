<?php

namespace App\Policies;

use App\Models\ReportTemplate;
use App\Models\User;

class ReportTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('report.expenses.view');
    }

    public function view(User $user, ReportTemplate $template): bool
    {
        if (! $user->hasPermission('report.expenses.view')) {
            return false;
        }

        return $template->is_built_in
            || $template->is_shared
            || $template->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('report.expenses.view');
    }

    public function update(User $user, ReportTemplate $template): bool
    {
        if ($template->is_built_in) {
            return false;
        }

        return $template->owner_user_id === $user->id
            || $user->hasPermission('admin.roles.manage');
    }

    public function delete(User $user, ReportTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}
