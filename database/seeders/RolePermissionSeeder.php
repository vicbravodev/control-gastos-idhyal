<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Assign initial permission sets to seeded roles, reproducing the legacy
     * hardcoded behavior so the system remains functional after the refactor.
     */
    public function run(): void
    {
        foreach (self::mapping() as $slug => $permissionSlugs) {
            $role = Role::query()->where('slug', $slug)->first();
            if ($role === null) {
                continue;
            }

            self::syncForRole($role, $permissionSlugs);
        }
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public static function syncForRole(Role $role, array $permissionSlugs): void
    {
        $ids = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($ids);
    }

    /**
     * Sync the canonical set for a known role slug. Used by tests / factories
     * to recreate baseline behavior on demand.
     */
    public static function syncForKnownRole(Role $role): void
    {
        $mapping = self::mapping();
        if (! isset($mapping[$role->slug])) {
            return;
        }

        self::syncForRole($role, $mapping[$role->slug]);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function mapping(): array
    {
        $allSlugs = [];
        foreach (PermissionSeeder::catalog() as $items) {
            foreach (array_keys($items) as $slug) {
                $allSlugs[] = $slug;
            }
        }

        return [
            'super_admin' => $allSlugs,
            'secretario_general' => [
                'expense_request.view_any',
                'expense_request.view_pending_balances',
                'expense_request.create',
                'expense_request.cancel_own',
                'expense_request.oversight',
                'vacation_request.view_any',
                'vacation_request.create',
                'vacation_request.approve',
                'vacation_request.reject',
                'vacation_request.update_own',
                'vacation_request.oversight',
                'budget.view_any',
                'budget.manage',
                'expense_concept.manage',
                'vacation_rule.manage',
                'approval_policy.view_any',
                'approval_policy.manage',
                'admin.users.view',
                'admin.users.manage',
                'admin.holidays.manage',
                'report.vacations.view',
            ],
            'contabilidad' => [
                'expense_request.view_any',
                'expense_request.view_pending_balances',
                'expense_request.create',
                'expense_request.cancel_own',
                'expense_request.review_accounting',
                'expense_request.record_payment',
                'expense_request.record_settlement',
                'expense_request.close_settlement',
                'expense_request.oversight',
                'expense_report.view_any',
                'expense_report.review',
                'payment.view_any',
                'payment.create',
                'payment.update',
                'budget.view_any',
                'budget.manage',
                'expense_concept.manage',
                'vacation_rule.manage',
                'vacation_request.create',
                'vacation_request.update_own',
                'report.expenses.view',
            ],
            'coord_regional' => [
                'expense_request.view_any',
                'expense_request.view_pending_balances',
                'expense_request.create',
                'expense_request.cancel_own',
                'expense_request.approve',
                'expense_request.reject',
                'expense_request.oversight',
                'vacation_request.view_any',
                'vacation_request.create',
                'vacation_request.approve',
                'vacation_request.reject',
                'vacation_request.update_own',
                'vacation_request.oversight',
            ],
            'coord_estatal' => [
                'expense_request.view_any',
                'expense_request.view_pending_balances',
                'expense_request.create',
                'expense_request.cancel_own',
                'expense_request.approve',
                'expense_request.reject',
                'expense_request.oversight',
                'vacation_request.view_any',
                'vacation_request.create',
                'vacation_request.approve',
                'vacation_request.reject',
                'vacation_request.update_own',
                'vacation_request.oversight',
            ],
            'asesor' => [
                'expense_request.create',
                'expense_request.cancel_own',
                'vacation_request.create',
                'vacation_request.update_own',
            ],
        ];
    }
}
