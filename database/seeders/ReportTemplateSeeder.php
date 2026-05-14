<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'cierre-mes',
                'name' => 'Cierre mensual',
                'description' => 'Solicitudes pagadas y cerradas del periodo',
                'icon' => 'calendar-check',
                'view' => 'resumen',
                'group_by' => null,
                'filters' => [
                    'period' => 'mtd',
                    'status' => 'paid',
                ],
            ],
            [
                'slug' => 'por-comprobar',
                'name' => 'Por comprobar',
                'description' => 'Gastos pendientes de comprobación',
                'icon' => 'receipt-text',
                'view' => 'detalle',
                'group_by' => null,
                'filters' => [
                    'period' => 'l90',
                    'status' => 'awaiting_expense_report',
                ],
            ],
            [
                'slug' => 'auditoria-cfdi',
                'name' => 'Auditoría CFDI',
                'description' => 'Conceptos que requieren factura',
                'icon' => 'file-check-2',
                'view' => 'pivote',
                'group_by' => 'concepto',
                'filters' => [
                    'period' => 'ytd',
                ],
            ],
            [
                'slug' => 'top-regiones',
                'name' => 'Por región',
                'description' => 'Distribución territorial del gasto',
                'icon' => 'map',
                'view' => 'pivote',
                'group_by' => 'region',
                'filters' => [
                    'period' => 'ytd',
                ],
            ],
            [
                'slug' => 'rechazadas',
                'name' => 'Rechazadas y canceladas',
                'description' => 'Análisis de fricción y motivos',
                'icon' => 'x-circle',
                'view' => 'detalle',
                'group_by' => null,
                'filters' => [
                    'period' => 'l90',
                    'status' => 'rejected',
                ],
            ],
            [
                'slug' => 'cuadre',
                'name' => 'Cuadre pendiente',
                'description' => 'Saldos por liquidar',
                'icon' => 'scale',
                'view' => 'detalle',
                'group_by' => null,
                'filters' => [
                    'period' => 'l90',
                    'status' => 'settlement_pending',
                ],
            ],
            [
                'slug' => 'ejecutivo',
                'name' => 'Resumen ejecutivo',
                'description' => 'KPIs y tendencias para dirección',
                'icon' => 'presentation',
                'view' => 'resumen',
                'group_by' => null,
                'filters' => [
                    'period' => 'ytd',
                    'compare' => true,
                ],
            ],
        ];

        foreach ($templates as $tpl) {
            ReportTemplate::query()->updateOrCreate(
                ['slug' => $tpl['slug']],
                array_merge($tpl, [
                    'owner_user_id' => null,
                    'is_built_in' => true,
                    'is_shared' => true,
                ]),
            );
        }
    }
}
