<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Master catalog of granular permissions, grouped by module.
     */
    public function run(): void
    {
        $position = 0;

        foreach (self::catalog() as $module => $items) {
            foreach ($items as $slug => $name) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module' => $module,
                        'position' => $position++,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function catalog(): array
    {
        return [
            'expense_requests' => [
                'expense_request.view_any' => 'Ver bandeja completa de solicitudes de gasto',
                'expense_request.view_pending_balances' => 'Ver bandeja de balances pendientes',
                'expense_request.create' => 'Crear solicitudes de gasto',
                'expense_request.approve' => 'Aprobar pasos pendientes',
                'expense_request.reject' => 'Rechazar pasos pendientes',
                'expense_request.cancel_own' => 'Cancelar la propia solicitud',
                'expense_request.review_accounting' => 'Revisar comprobaciones contables',
                'expense_request.record_payment' => 'Registrar pago de solicitud aprobada',
                'expense_request.record_settlement' => 'Registrar liquidación / cierre de balance',
                'expense_request.close_settlement' => 'Cerrar liquidación',
                'expense_request.oversight' => 'Supervisión amplia de solicitudes de gasto',
            ],
            'expense_reports' => [
                'expense_report.view_any' => 'Ver bandeja de comprobaciones',
                'expense_report.review' => 'Revisar/Aprobar/Rechazar comprobaciones',
            ],
            'payments' => [
                'payment.view_any' => 'Ver bandeja de pagos',
                'payment.create' => 'Registrar pagos',
                'payment.update' => 'Editar pagos',
            ],
            'vacation_requests' => [
                'vacation_request.view_any' => 'Ver bandeja completa de vacaciones',
                'vacation_request.create' => 'Crear solicitudes de vacaciones',
                'vacation_request.approve' => 'Aprobar solicitudes de vacaciones',
                'vacation_request.reject' => 'Rechazar solicitudes de vacaciones',
                'vacation_request.update_own' => 'Modificar la propia solicitud de vacaciones',
                'vacation_request.oversight' => 'Supervisión amplia de vacaciones',
            ],
            'budgets' => [
                'budget.view_any' => 'Ver presupuestos',
                'budget.manage' => 'Crear/editar/eliminar presupuestos',
                'expense_concept.manage' => 'Gestionar conceptos de gasto',
            ],
            'approval_policies' => [
                'approval_policy.view_any' => 'Ver políticas de aprobación',
                'approval_policy.manage' => 'Crear/editar políticas de aprobación',
                'approval_policy.delete' => 'Eliminar políticas de aprobación',
            ],
            'vacation_rules' => [
                'vacation_rule.manage' => 'Gestionar reglas de vacaciones',
            ],
            'admin' => [
                'admin.users.view' => 'Ver directorio de personal',
                'admin.users.manage' => 'Gestionar directorio de personal',
                'admin.roles.manage' => 'Gestionar roles y permisos',
                'admin.departments.manage' => 'Gestionar departamentos',
                'admin.regions.manage' => 'Gestionar regiones',
                'admin.states.manage' => 'Gestionar estados',
            ],
            'reports' => [
                'report.expenses.view' => 'Ver y descargar reporte de gastos',
            ],
            'system' => [
                'system.bypass_all' => 'Bypass total de autorización (acceso completo)',
            ],
        ];
    }
}
