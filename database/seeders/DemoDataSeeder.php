<?php

namespace Database\Seeders;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\BudgetLedgerEntryType;
use App\Enums\DeliveryMethod;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\PaymentMethod;
use App\Enums\SettlementStatus;
use App\Enums\VacationRequestStatus;
use App\Models\Budget;
use App\Models\BudgetLedgerEntry;
use App\Models\Department;
use App\Models\DocumentEvent;
use App\Models\ExpenseConcept;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Payment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\State;
use App\Models\User;
use App\Models\VacationEntitlement;
use App\Models\VacationEntitlementAdjustment;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use App\Models\VacationRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Populates the database with realistic demo data for manual UI validation.
 * Creates users for every role, expense requests at every lifecycle stage,
 * vacation requests, budgets, and a full audit-event trail.
 *
 * Run: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private string $defaultPassword;

    /** @var array<string, Role> */
    private array $roles = [];

    /** @var array<string, User> */
    private array $users = [];

    private Region $regionNorte;

    private Region $regionSur;

    private State $stateNL;

    private State $stateJal;

    private State $stateOax;

    private Role $coordRegionalRole;

    private Role $contabilidadRole;

    private Role $secretarioRole;

    /**
     * @var array<string, int>
     */
    private array $expenseConceptIds = [];

    /** @var array<string, Department> */
    private array $departments = [];

    public function run(): void
    {
        $this->defaultPassword = Hash::make('password');

        $this->seedRegionsAndStates();
        $this->seedDepartments();
        $this->loadRoles();
        $this->seedUsers();
        $this->seedVacationRulesAndEntitlements();
        $this->seedVacationEntitlementAdjustments();
        $this->seedExpenseConcepts();
        $this->seedBudgets();
        $this->seedExpenseRequests();
        $this->seedReimbursementExpenseRequests();
        $this->seedVacationRequests();
    }

    private function seedDepartments(): void
    {
        $definitions = [
            'tecnologia' => ['code' => 'TI', 'name' => 'Tecnología', 'position' => 10],
            'contabilidad' => ['code' => 'CONT', 'name' => 'Contabilidad', 'position' => 20],
            'operaciones' => ['code' => 'OPS', 'name' => 'Operaciones', 'position' => 30],
            'asesoria' => ['code' => 'ASE', 'name' => 'Asesoría', 'position' => 40],
            'direccion' => ['code' => 'DIR', 'name' => 'Dirección', 'position' => 50],
        ];

        foreach ($definitions as $key => $meta) {
            $this->departments[$key] = Department::query()->updateOrCreate(
                ['code' => $meta['code']],
                ['name' => $meta['name'], 'is_active' => true, 'position' => $meta['position']],
            );
        }
    }

    private function seedRegionsAndStates(): void
    {
        $this->regionNorte = Region::query()->updateOrCreate(
            ['code' => 'NORTE'],
            ['name' => 'Región Norte'],
        );

        $this->regionSur = Region::query()->updateOrCreate(
            ['code' => 'SUR'],
            ['name' => 'Región Sur'],
        );

        $this->stateNL = State::query()->updateOrCreate(
            ['code' => 'NL'],
            ['name' => 'Nuevo León', 'region_id' => $this->regionNorte->id],
        );

        $this->stateJal = State::query()->updateOrCreate(
            ['code' => 'JAL'],
            ['name' => 'Jalisco', 'region_id' => $this->regionNorte->id],
        );

        $this->stateOax = State::query()->updateOrCreate(
            ['code' => 'OAX'],
            ['name' => 'Oaxaca', 'region_id' => $this->regionSur->id],
        );
    }

    private function loadRoles(): void
    {
        $this->roles = Role::all()->keyBy('slug')->all();
        $this->coordRegionalRole = $this->roles['coord_regional'];
        $this->contabilidadRole = $this->roles['contabilidad'];
        $this->secretarioRole = $this->roles['secretario_general'];
    }

    private function seedUsers(): void
    {
        $specs = [
            'super_admin' => [
                'name' => 'Admin Demo',
                'email' => 'admin@demo.com',
                'username' => 'admin',
                'region' => null,
                'state' => null,
                'department' => 'direccion',
            ],
            'secretario_general' => [
                'name' => 'Secretario Demo',
                'email' => 'secretario@demo.com',
                'username' => 'secretario',
                'region' => $this->regionNorte,
                'state' => null,
                'department' => 'direccion',
            ],
            'contabilidad' => [
                'name' => 'Contadora Demo',
                'email' => 'contabilidad@demo.com',
                'username' => 'contabilidad',
                'region' => null,
                'state' => null,
                'department' => 'contabilidad',
            ],
            'coord_regional' => [
                'name' => 'Coord. Regional Norte',
                'email' => 'coord.regional@demo.com',
                'username' => 'coord_regional',
                'region' => $this->regionNorte,
                'state' => null,
                'department' => 'operaciones',
            ],
            'coord_estatal' => [
                'name' => 'Coord. Estatal NL',
                'email' => 'coord.estatal@demo.com',
                'username' => 'coord_estatal',
                'region' => $this->regionNorte,
                'state' => $this->stateNL,
                'department' => 'operaciones',
            ],
            'asesor' => [
                'name' => 'Asesor Demo',
                'email' => 'asesor@demo.com',
                'username' => 'asesor',
                'region' => $this->regionNorte,
                'state' => $this->stateNL,
                'department' => 'asesoria',
            ],
        ];

        foreach ($specs as $roleSlug => $spec) {
            $this->users[$roleSlug] = User::query()->updateOrCreate(
                ['email' => $spec['email']],
                [
                    'name' => $spec['name'],
                    'username' => $spec['username'],
                    'email_verified_at' => now(),
                    'password' => $this->defaultPassword,
                    'role_id' => $this->roles[$roleSlug]->id,
                    'region_id' => $spec['region']?->id,
                    'state_id' => $spec['state']?->id,
                    'department_id' => $this->departments[$spec['department']]?->id,
                    'phone' => fake()->numerify('55########'),
                    'hire_date' => '2019-03-01',
                ],
            );
        }

        $this->users['asesor2'] = User::query()->updateOrCreate(
            ['email' => 'asesor2@demo.com'],
            [
                'name' => 'Asesor Jalisco',
                'username' => 'asesor_jal',
                'email_verified_at' => now(),
                'password' => $this->defaultPassword,
                'role_id' => $this->roles['asesor']->id,
                'region_id' => $this->regionNorte->id,
                'state_id' => $this->stateJal->id,
                'department_id' => $this->departments['asesoria']->id,
                'phone' => fake()->numerify('33########'),
                'hire_date' => '2020-07-01',
            ],
        );

        $this->users['asesor3'] = User::query()->updateOrCreate(
            ['email' => 'asesor3@demo.com'],
            [
                'name' => 'Asesor Oaxaca',
                'username' => 'asesor_oax',
                'email_verified_at' => now(),
                'password' => $this->defaultPassword,
                'role_id' => $this->roles['asesor']->id,
                'region_id' => $this->regionSur->id,
                'state_id' => $this->stateOax->id,
                'department_id' => $this->departments['asesoria']->id,
                'phone' => fake()->numerify('95########'),
                'hire_date' => now()->subMonths(6)->toDateString(),
            ],
        );

        $this->users['coord_estatal2'] = User::query()->updateOrCreate(
            ['email' => 'coord.estatal.sur@demo.com'],
            [
                'name' => 'Coord. Estatal Oaxaca',
                'username' => 'coord_estatal_oax',
                'email_verified_at' => now(),
                'password' => $this->defaultPassword,
                'role_id' => $this->roles['coord_estatal']->id,
                'region_id' => $this->regionSur->id,
                'state_id' => $this->stateOax->id,
                'department_id' => $this->departments['operaciones']->id,
                'phone' => fake()->numerify('95########'),
                'hire_date' => '2018-01-10',
            ],
        );
    }

    private function seedVacationRulesAndEntitlements(): void
    {
        $rule = VacationRule::query()->updateOrCreate(
            ['code' => 'LFT_2023'],
            [
                'name' => 'Ley Federal del Trabajo 2023',
                'min_years_service' => 1.0,
                'max_years_service' => null,
                'days_granted_per_year' => 12,
                'max_days_per_request' => 10,
                'max_days_per_month' => 10,
                'max_days_per_quarter' => 10,
                'max_days_per_year' => 12,
                'blackout_dates' => [],
                'sort_order' => 1,
            ],
        );

        foreach (['asesor', 'asesor2', 'coord_estatal', 'coord_estatal2'] as $key) {
            VacationEntitlement::query()->updateOrCreate(
                ['user_id' => $this->users[$key]->id, 'calendar_year' => now()->year],
                [
                    'days_allocated' => 12,
                    'days_used' => $key === 'asesor' ? 3 : 0,
                    'vacation_rule_id' => $rule->id,
                ],
            );
        }
    }

    /**
     * Demo del flujo de devolución / corrección de saldo de vacaciones.
     * Ejemplo de la junta: el asesor solicitó 8 días, sólo tomó 7 → +1 día devuelto.
     * También un caso negativo (corrección) y un premio para coord_estatal.
     */
    private function seedVacationEntitlementAdjustments(): void
    {
        $year = (int) now()->year;
        $admin = $this->users['super_admin'];

        $adjustments = [
            [
                'user' => 'asesor',
                'days' => 1,
                'reason' => 'Devolución: solicitó 8 días, sólo tomó 7.',
                'created_at' => now()->subDays(15),
            ],
            [
                'user' => 'asesor2',
                'days' => 2,
                'reason' => 'Premio por desempeño anual.',
                'created_at' => now()->subDays(60),
            ],
            [
                'user' => 'asesor3',
                'days' => -1,
                'reason' => 'Corrección por error administrativo.',
                'created_at' => now()->subDays(7),
            ],
            [
                'user' => 'coord_estatal',
                'days' => 3,
                'reason' => 'Compensación por tiempo extra trabajado.',
                'created_at' => now()->subDays(30),
            ],
        ];

        foreach ($adjustments as $a) {
            $user = $this->users[$a['user']];

            $existing = VacationEntitlementAdjustment::query()
                ->where('user_id', $user->id)
                ->where('calendar_year', $year)
                ->where('reason', $a['reason'])
                ->first();

            if ($existing !== null) {
                continue;
            }

            $adjustment = VacationEntitlementAdjustment::query()->create([
                'user_id' => $user->id,
                'calendar_year' => $year,
                'days' => $a['days'],
                'reason' => $a['reason'],
                'granted_by_user_id' => $admin->id,
                'created_at' => $a['created_at'],
                'updated_at' => $a['created_at'],
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $user->getMorphClass(),
                'subject_id' => $user->id,
                'event_type' => DocumentEventType::VacationEntitlementAdjusted,
                'actor_user_id' => $admin->id,
                'note' => $a['reason'],
                'metadata' => [
                    'adjustment_id' => $adjustment->id,
                    'calendar_year' => $year,
                    'days' => $a['days'],
                ],
            ]);
        }
    }

    private function seedBudgets(): void
    {
        $start = now()->startOfYear()->toDateString();
        $end = now()->endOfYear()->toDateString();

        Budget::query()->updateOrCreate(
            ['budgetable_type' => 'region', 'budgetable_id' => $this->regionNorte->id, 'period_starts_on' => $start],
            ['period_ends_on' => $end, 'amount_limit_cents' => 50_000_000, 'priority' => 1],
        );

        Budget::query()->updateOrCreate(
            ['budgetable_type' => 'region', 'budgetable_id' => $this->regionSur->id, 'period_starts_on' => $start],
            ['period_ends_on' => $end, 'amount_limit_cents' => 30_000_000, 'priority' => 1],
        );

        Budget::query()->updateOrCreate(
            ['budgetable_type' => 'state', 'budgetable_id' => $this->stateNL->id, 'period_starts_on' => $start],
            ['period_ends_on' => $end, 'amount_limit_cents' => 20_000_000, 'priority' => 2],
        );

        Budget::query()->updateOrCreate(
            ['budgetable_type' => 'state', 'budgetable_id' => $this->stateOax->id, 'period_starts_on' => $start],
            ['period_ends_on' => $end, 'amount_limit_cents' => 15_000_000, 'priority' => 2],
        );
    }

    private function seedExpenseConcepts(): void
    {
        $definitions = [
            'viaticos' => ['name' => 'Viáticos y traslados', 'sort_order' => 10],
            'combustible' => ['name' => 'Combustible', 'sort_order' => 20],
            'renta_espacio' => ['name' => 'Renta de espacios', 'sort_order' => 30],
            'capacitacion' => ['name' => 'Capacitación y talleres', 'sort_order' => 40],
            'transporte' => ['name' => 'Transporte y arrendamiento', 'sort_order' => 50],
            'hospedaje' => ['name' => 'Hospedaje', 'sort_order' => 60],
            'papeleria' => ['name' => 'Papelería y oficina', 'sort_order' => 70],
            'alimentos' => ['name' => 'Alimentación en jornada', 'sort_order' => 80],
            'equipo' => ['name' => 'Equipo y tecnología', 'sort_order' => 90],
        ];

        foreach ($definitions as $key => $meta) {
            $concept = ExpenseConcept::query()->updateOrCreate(
                ['name' => $meta['name']],
                ['is_active' => true, 'sort_order' => $meta['sort_order']],
            );
            $this->expenseConceptIds[$key] = $concept->id;
        }
    }

    // ─── Expense requests at every lifecycle stage ───────────────────

    private function seedExpenseRequests(): void
    {
        $this->expenseSubmitted();
        $this->expensePendingCoordApproval();
        $this->expensePendingCoordApproval2();
        $this->expenseApprovalInProgress();
        $this->expenseRejected();
        $this->expenseCancelled();
        $this->expenseApprovedPendingPayment();
        $this->expensePaid();
        $this->expenseAwaitingReport();
        $this->expenseReportInReview();
        $this->expenseReportApproved();
        $this->expenseSettlementPending();
        $this->expenseClosed();
        $this->expenseClosedWithReturn();
    }

    /**
     * Recién creada, aún no entra a flujo (submitted).
     */
    private function expenseSubmitted(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::Submitted,
            150_000,
            'viaticos',
            'Visita a comunidad rural.',
        );

        $this->addApprovalSteps($er, [
            ['role' => $this->coordRegionalRole, 'status' => ApprovalInstanceStatus::Pending],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Pending],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor'], '-');

        return $er;
    }

    /**
     * En aprobación, paso 1 (coord. regional) pendiente — visible para el coordinador.
     */
    private function expensePendingCoordApproval(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::ApprovalInProgress,
            175_000,
            'combustible',
            'Traslado a comunidades rurales.',
        );

        $this->addApprovalSteps($er, [
            ['role' => $this->coordRegionalRole, 'status' => ApprovalInstanceStatus::Pending],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Pending],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor'], '-');

        return $er;
    }

    /**
     * En aprobación, paso 1 (coord. regional) pendiente — segunda solicitud visible para el coordinador.
     */
    private function expensePendingCoordApproval2(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['coord_estatal'],
            ExpenseRequestStatus::ApprovalInProgress,
            420_000,
            'renta_espacio',
            'Capacitación estatal.',
        );

        $this->addApprovalSteps($er, [
            ['role' => $this->coordRegionalRole, 'status' => ApprovalInstanceStatus::Pending],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Pending],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['coord_estatal'], '-');

        return $er;
    }

    /**
     * Primer paso aprobado, segundo pendiente.
     */
    private function expenseApprovalInProgress(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor2'],
            ExpenseRequestStatus::ApprovalInProgress,
            250_000,
            'capacitacion',
            'Talleres regionales.',
        );

        $this->addApprovalSteps($er, [
            [
                'role' => $this->coordRegionalRole,
                'status' => ApprovalInstanceStatus::Approved,
                'approver' => $this->users['coord_regional'],
                'note' => 'Aprobado, concepto alineado a plan.',
            ],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Pending],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor2'], '-');

        return $er;
    }

    /**
     * Rechazada por coord. regional.
     */
    private function expenseRejected(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor3'],
            ExpenseRequestStatus::Rejected,
            500_000,
            'transporte',
            'Vehículo premium para traslado.',
        );

        $this->addApprovalSteps($er, [
            [
                'role' => $this->coordRegionalRole,
                'status' => ApprovalInstanceStatus::Rejected,
                'approver' => $this->users['coord_regional'],
                'note' => 'El monto excede el presupuesto asignado.',
            ],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Skipped],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor3'], '-');
        $this->addEvent($er, DocumentEventType::Rejection, $this->users['coord_regional'], 'Rechazada por exceso de presupuesto.');

        return $er;
    }

    /**
     * Cancelada por el solicitante.
     */
    private function expenseCancelled(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::Cancelled,
            80_000,
            'hospedaje',
            'Reunión cancelada por cambio de fecha.',
        );

        $this->addApprovalSteps($er, [
            ['role' => $this->coordRegionalRole, 'status' => ApprovalInstanceStatus::Skipped],
            ['role' => $this->contabilidadRole, 'status' => ApprovalInstanceStatus::Skipped],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor'], '-');
        $this->addEvent($er, DocumentEventType::Cancellation, $this->users['asesor'], 'La reunión se reprogramó.');

        return $er;
    }

    /**
     * Aprobada completamente, pendiente de pago.
     */
    private function expenseApprovedPendingPayment(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['coord_estatal'],
            ExpenseRequestStatus::PendingPayment,
            320_000,
            'transporte',
            'Boletos de avión para supervisión estatal.',
            320_000,
        );

        $this->addApprovalSteps($er, [
            [
                'role' => $this->coordRegionalRole,
                'status' => ApprovalInstanceStatus::Approved,
                'approver' => $this->users['coord_regional'],
                'note' => 'Aprobado.',
            ],
            [
                'role' => $this->contabilidadRole,
                'status' => ApprovalInstanceStatus::Approved,
                'approver' => $this->users['contabilidad'],
                'note' => 'Presupuesto disponible, aprobado.',
            ],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['coord_estatal'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Cadena de aprobación completada.');

        $this->commitBudget($er);

        return $er;
    }

    /**
     * Pagada, esperando comprobación.
     */
    private function expensePaid(): ExpenseRequest
    {
        return $this->expenseAwaitingReport();
    }

    /**
     * Pagada y esperando comprobación (awaiting_expense_report).
     */
    private function expenseAwaitingReport(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::AwaitingExpenseReport,
            200_000,
            'hospedaje',
            'Gira de trabajo.',
            200_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 200_000,
            'payment_method' => PaymentMethod::Transfer,
            'paid_on' => now()->subDays(5)->toDateString(),
            'transfer_reference' => 'TRF-20260318001',
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago vía transferencia registrado.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    /**
     * Comprobación en revisión por contabilidad.
     */
    private function expenseReportInReview(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor2'],
            ExpenseRequestStatus::ExpenseReportInReview,
            180_000,
            'papeleria',
            'Oficina regional.',
            180_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 180_000,
            'payment_method' => PaymentMethod::Cash,
            'paid_on' => now()->subDays(10)->toDateString(),
            'transfer_reference' => null,
        ]);

        ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::AccountingReview,
            'reported_amount_cents' => 175_000,
            'document_type' => ExpenseReportDocumentType::Factura,
            'submitted_at' => now()->subDays(2),
            'cfdi_uuid' => '51DA9325-6DF1-4064-AAAB-386F7BC84AD6',
            'cfdi_emisor_rfc' => 'STK230120D23',
            'cfdi_emisor_nombre' => 'SISTEMAS TECNOLOGICOS KAPPU',
            'cfdi_receptor_rfc' => 'IDH800514B86',
            'cfdi_receptor_nombre' => 'IDHYAL',
            'cfdi_fecha' => now()->subDays(3),
            'cfdi_serie' => 'A',
            'cfdi_folio' => '26',
            'cfdi_forma_pago' => '03',
            'cfdi_metodo_pago' => 'PUE',
            'cfdi_uso_cfdi' => 'G03',
            'cfdi_conceptos' => [
                [
                    'descripcion' => 'servicios de la nube 24 meses',
                    'cantidad' => 1,
                    'unidad' => 'Unidad de servicio',
                    'valor_unitario' => 26500.0,
                    'importe' => 26500.0,
                    'clave_prod_serv' => '81112006',
                ],
            ],
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor2'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago en efectivo registrado.');
        $this->addEvent($er, DocumentEventType::ExpenseReportSubmitted, $this->users['asesor2'], 'Comprobación enviada.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    /**
     * Comprobación aprobada, liquidación pendiente.
     */
    private function expenseReportApproved(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['coord_estatal'],
            ExpenseRequestStatus::ExpenseReportApproved,
            120_000,
            'alimentos',
            'Jornada de campo.',
            120_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 120_000,
            'payment_method' => PaymentMethod::Transfer,
            'paid_on' => now()->subDays(15)->toDateString(),
            'transfer_reference' => 'TRF-20260308002',
        ]);

        ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 118_500,
            'document_type' => ExpenseReportDocumentType::Recibo,
            'submitted_at' => now()->subDays(8),
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['coord_estatal'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago registrado.');
        $this->addEvent($er, DocumentEventType::ExpenseReportSubmitted, $this->users['coord_estatal'], 'Comprobación enviada.');
        $this->addEvent($er, DocumentEventType::ExpenseReportApproved, $this->users['contabilidad'], 'Comprobación correcta.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    /**
     * Settlement calculado, pendiente de liquidación.
     */
    private function expenseSettlementPending(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor3'],
            ExpenseRequestStatus::SettlementPending,
            300_000,
            'equipo',
            'Oficina estatal.',
            300_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 300_000,
            'payment_method' => PaymentMethod::Transfer,
            'paid_on' => now()->subDays(20)->toDateString(),
            'transfer_reference' => 'TRF-20260303003',
        ]);

        $report = ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 285_000,
            'document_type' => ExpenseReportDocumentType::Factura,
            'submitted_at' => now()->subDays(12),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::PendingUserReturn,
            'basis_amount_cents' => 300_000,
            'reported_amount_cents' => 285_000,
            'difference_cents' => 15_000,
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor3'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago registrado.');
        $this->addEvent($er, DocumentEventType::ExpenseReportSubmitted, $this->users['asesor3'], 'Comprobación enviada.');
        $this->addEvent($er, DocumentEventType::ExpenseReportApproved, $this->users['contabilidad'], 'Aprobada.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    /**
     * Ciclo completo cerrado sin diferencia (0 balance).
     */
    private function expenseClosed(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::Closed,
            100_000,
            'papeleria',
            'Material informativo.',
            100_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 100_000,
            'payment_method' => PaymentMethod::Cash,
            'paid_on' => now()->subDays(30)->toDateString(),
            'transfer_reference' => null,
        ]);

        $report = ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 100_000,
            'document_type' => ExpenseReportDocumentType::Factura,
            'submitted_at' => now()->subDays(25),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::Closed,
            'basis_amount_cents' => 100_000,
            'reported_amount_cents' => 100_000,
            'difference_cents' => 0,
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago registrado.');
        $this->addEvent($er, DocumentEventType::ExpenseReportSubmitted, $this->users['asesor'], 'Comprobación enviada.');
        $this->addEvent($er, DocumentEventType::ExpenseReportApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::SettlementClosed, $this->users['contabilidad'], 'Sin diferencia, ciclo cerrado.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    /**
     * Ciclo cerrado con devolución del usuario (sobrante).
     */
    private function expenseClosedWithReturn(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor2'],
            ExpenseRequestStatus::Closed,
            400_000,
            'renta_espacio',
            'Evento regional.',
            400_000,
        );

        $this->addFullApprovalChain($er);

        Payment::query()->create([
            'expense_request_id' => $er->id,
            'recorded_by_user_id' => $this->users['contabilidad']->id,
            'amount_cents' => 400_000,
            'payment_method' => PaymentMethod::Transfer,
            'paid_on' => now()->subDays(45)->toDateString(),
            'transfer_reference' => 'TRF-20260207004',
        ]);

        $report = ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 350_000,
            'document_type' => ExpenseReportDocumentType::Recibo,
            'submitted_at' => now()->subDays(35),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::Closed,
            'basis_amount_cents' => 400_000,
            'reported_amount_cents' => 350_000,
            'difference_cents' => 50_000,
        ]);

        $this->addEvent($er, DocumentEventType::ExpenseRequestSubmitted, $this->users['asesor2'], '-');
        $this->addEvent($er, DocumentEventType::ExpenseRequestChainApproved, $this->users['contabilidad'], 'Aprobada.');
        $this->addEvent($er, DocumentEventType::ExpenseRequestPaid, $this->users['contabilidad'], 'Pago registrado.');
        $this->addEvent($er, DocumentEventType::ExpenseReportSubmitted, $this->users['asesor2'], 'Comprobación enviada.');
        $this->addEvent($er, DocumentEventType::ExpenseReportApproved, $this->users['contabilidad'], 'Comprobación correcta.');
        $this->addEvent($er, DocumentEventType::SettlementLiquidationRecorded, $this->users['contabilidad'], 'Devolución de $500 recibida.');
        $this->addEvent($er, DocumentEventType::SettlementClosed, $this->users['contabilidad'], 'Liquidación completada, ciclo cerrado.');

        $this->commitBudget($er);
        $this->spendBudget($er);

        return $er;
    }

    // ─── Reimbursements (comprobaciones directas sin solicitud previa) ─

    private function seedReimbursementExpenseRequests(): void
    {
        $this->reimbursementInAccountingReview();
        $this->reimbursementSettlementPendingCompanyPayment();
        $this->reimbursementClosed();
    }

    /**
     * Reembolso recién enviado: el asesor pagó de su bolsa una factura, subió
     * el comprobante directamente y contabilidad la verá en su bandeja.
     */
    private function reimbursementInAccountingReview(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor'],
            ExpenseRequestStatus::ExpenseReportInReview,
            85_000,
            'alimentos',
            'Almuerzo con cliente — pagué de mi bolsa.',
            85_000,
            isReimbursement: true,
        );

        ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::AccountingReview,
            'reported_amount_cents' => 85_000,
            'document_type' => ExpenseReportDocumentType::Factura,
            'submitted_at' => now()->subDays(1),
        ]);

        $this->addEvent(
            $er,
            DocumentEventType::ExpenseRequestReimbursementCreated,
            $this->users['asesor'],
            'Comprobación directa enviada a contabilidad.',
        );

        return $er;
    }

    /**
     * Reembolso aprobado por contabilidad: la empresa debe pagarle al usuario.
     * Settlement quedó como pending_company_payment, basis = 0.
     */
    private function reimbursementSettlementPendingCompanyPayment(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['asesor2'],
            ExpenseRequestStatus::SettlementPending,
            45_000,
            'papeleria',
            'Compra urgente de toner — recibo de la papelería.',
            45_000,
            isReimbursement: true,
        );

        $report = ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 45_000,
            'document_type' => ExpenseReportDocumentType::Recibo,
            'submitted_at' => now()->subDays(4),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::PendingCompanyPayment,
            'basis_amount_cents' => 0,
            'reported_amount_cents' => 45_000,
            'difference_cents' => -45_000,
        ]);

        $this->addEvent(
            $er,
            DocumentEventType::ExpenseRequestReimbursementCreated,
            $this->users['asesor2'],
            'Comprobación directa enviada a contabilidad.',
        );
        $this->addEvent(
            $er,
            DocumentEventType::ExpenseReportApproved,
            $this->users['contabilidad'],
            'Comprobación correcta, queda pendiente pagar al solicitante.',
        );

        return $er;
    }

    /**
     * Reembolso completo y ya liquidado.
     */
    private function reimbursementClosed(): ExpenseRequest
    {
        $er = $this->createExpenseRequest(
            $this->users['coord_estatal'],
            ExpenseRequestStatus::Closed,
            22_000,
            'transporte',
            'Taxi para junta extraordinaria — pagué de mi bolsa.',
            22_000,
            isReimbursement: true,
        );

        $report = ExpenseReport::query()->create([
            'expense_request_id' => $er->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => 22_000,
            'document_type' => ExpenseReportDocumentType::Factura,
            'submitted_at' => now()->subDays(20),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::Closed,
            'basis_amount_cents' => 0,
            'reported_amount_cents' => 22_000,
            'difference_cents' => -22_000,
        ]);

        $this->addEvent(
            $er,
            DocumentEventType::ExpenseRequestReimbursementCreated,
            $this->users['coord_estatal'],
            'Comprobación directa enviada a contabilidad.',
        );
        $this->addEvent(
            $er,
            DocumentEventType::ExpenseReportApproved,
            $this->users['contabilidad'],
            'Aprobada.',
        );
        $this->addEvent(
            $er,
            DocumentEventType::SettlementLiquidationRecorded,
            $this->users['contabilidad'],
            'Reembolso depositado al solicitante.',
        );
        $this->addEvent(
            $er,
            DocumentEventType::SettlementClosed,
            $this->users['contabilidad'],
            'Liquidación completada.',
        );

        return $er;
    }

    // ─── Vacation requests ───────────────────────────────────────────

    private function seedVacationRequests(): void
    {
        $this->vacationApproved();
        $this->vacationPendingApproval();
        $this->vacationRejected();
        $this->vacationCompleted();
        $this->vacationStaleAboutToExpire();
        $this->vacationAlreadyExpired();
    }

    /**
     * Solicitud creada hace más de 7 días que aún no se aprueba — el comando
     * `vacation-requests:expire-stale-pending` la detectará en la próxima corrida.
     */
    private function vacationStaleAboutToExpire(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['asesor3']->id,
            'status' => VacationRequestStatus::Submitted,
            'folio' => 'VAC-'.now()->year.'-DEMO-5',
            'starts_on' => now()->addDays(40)->toDateString(),
            'ends_on' => now()->addDays(44)->toDateString(),
            'business_days_count' => 3,
            'created_at' => now()->subDays(9),
            'updated_at' => now()->subDays(9),
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Pending,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
        ]);
    }

    /**
     * Solicitud ya marcada como expirada por el comando programado.
     * Sus días NO consumen saldo, así que el usuario los recuperó.
     */
    private function vacationAlreadyExpired(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['coord_estatal2']->id,
            'status' => VacationRequestStatus::Expired,
            'folio' => 'VAC-'.now()->year.'-DEMO-6',
            'starts_on' => now()->addDays(60)->toDateString(),
            'ends_on' => now()->addDays(62)->toDateString(),
            'business_days_count' => 2,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(11),
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Skipped,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
        ]);

        DocumentEvent::query()->create([
            'subject_type' => $vr->getMorphClass(),
            'subject_id' => $vr->id,
            'event_type' => DocumentEventType::VacationRequestExpired,
            'actor_user_id' => null,
            'note' => 'Expirada automáticamente tras 7 días sin aprobación.',
            'metadata' => [
                'reason' => 'expired_by_inactivity',
                'expiration_days' => 7,
            ],
        ]);
    }

    private function vacationApproved(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['asesor']->id,
            'status' => VacationRequestStatus::Approved,
            'folio' => 'VAC-' . now()->year . '-DEMO-1',
            'starts_on' => now()->addDays(10)->toDateString(),
            'ends_on' => now()->addDays(14)->toDateString(),
            'business_days_count' => 3,
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Approved,
            'approver_user_id' => $this->users['secretario_general']->id,
            'note' => 'Aprobada.',
            'acted_at' => now()->subDays(2),
        ]);
    }

    private function vacationPendingApproval(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['asesor2']->id,
            'status' => VacationRequestStatus::ApprovalInProgress,
            'folio' => 'VAC-' . now()->year . '-DEMO-2',
            'starts_on' => now()->addDays(20)->toDateString(),
            'ends_on' => now()->addDays(26)->toDateString(),
            'business_days_count' => 5,
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Pending,
            'approver_user_id' => null,
            'note' => null,
            'acted_at' => null,
        ]);
    }

    private function vacationRejected(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['coord_estatal']->id,
            'status' => VacationRequestStatus::Rejected,
            'folio' => 'VAC-' . now()->year . '-DEMO-3',
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(19)->toDateString(),
            'business_days_count' => 11,
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Rejected,
            'approver_user_id' => $this->users['secretario_general']->id,
            'note' => 'Excede el máximo de días por solicitud.',
            'acted_at' => now()->subDay(),
        ]);
    }

    private function vacationCompleted(): void
    {
        $vr = VacationRequest::query()->create([
            'user_id' => $this->users['asesor']->id,
            'status' => VacationRequestStatus::Completed,
            'folio' => 'VAC-' . now()->year . '-DEMO-4',
            'starts_on' => now()->subDays(20)->toDateString(),
            'ends_on' => now()->subDays(16)->toDateString(),
            'business_days_count' => 3,
        ]);

        VacationRequestApproval::query()->create([
            'vacation_request_id' => $vr->id,
            'step_order' => 1,
            'role_id' => $this->secretarioRole->id,
            'status' => ApprovalInstanceStatus::Approved,
            'approver_user_id' => $this->users['secretario_general']->id,
            'note' => 'Aprobada.',
            'acted_at' => now()->subDays(25),
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createExpenseRequest(
        User $user,
        ExpenseRequestStatus $status,
        int $requestedCents,
        string $expenseConceptKey,
        ?string $conceptDescription = null,
        ?int $approvedCents = null,
        bool $isReimbursement = false,
    ): ExpenseRequest {
        static $folioCounter = 0;
        $folioCounter++;

        $conceptId = $this->expenseConceptIds[$expenseConceptKey] ?? null;
        if ($conceptId === null) {
            throw new \InvalidArgumentException("Unknown expense concept key: {$expenseConceptKey}");
        }

        $folioPrefix = $isReimbursement ? 'REIMB' : 'EXP';

        return ExpenseRequest::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'folio' => sprintf('%s-%d-DEMO-%03d', $folioPrefix, now()->year, $folioCounter),
            'requested_amount_cents' => $requestedCents,
            'approved_amount_cents' => $approvedCents,
            'expense_concept_id' => $conceptId,
            'concept_description' => $conceptDescription,
            'delivery_method' => $isReimbursement
                ? DeliveryMethod::Transfer
                : fake()->randomElement(DeliveryMethod::cases()),
            'is_reimbursement' => $isReimbursement,
        ]);
    }

    /**
     * @param array<int, array{role: Role, status: ApprovalInstanceStatus, approver?: User, note?: string}> $steps
     */
    private function addApprovalSteps(ExpenseRequest $er, array $steps): void
    {
        foreach ($steps as $i => $step) {
            ExpenseRequestApproval::query()->create([
                'expense_request_id' => $er->id,
                'step_order' => $i + 1,
                'role_id' => $step['role']->id,
                'status' => $step['status'],
                'approver_user_id' => isset($step['approver']) ? $step['approver']->id : null,
                'note' => $step['note'] ?? null,
                'acted_at' => $step['status'] !== ApprovalInstanceStatus::Pending ? now()->subDays(rand(1, 10)) : null,
            ]);
        }
    }

    private function addFullApprovalChain(ExpenseRequest $er): void
    {
        $this->addApprovalSteps($er, [
            [
                'role' => $this->coordRegionalRole,
                'status' => ApprovalInstanceStatus::Approved,
                'approver' => $this->users['coord_regional'],
                'note' => 'Aprobado.',
            ],
            [
                'role' => $this->contabilidadRole,
                'status' => ApprovalInstanceStatus::Approved,
                'approver' => $this->users['contabilidad'],
                'note' => 'Aprobado.',
            ],
        ]);
    }

    private function addEvent(ExpenseRequest $er, DocumentEventType $type, User $actor, string $note): void
    {
        DocumentEvent::query()->create([
            'subject_type' => $er->getMorphClass(),
            'subject_id' => $er->id,
            'event_type' => $type,
            'actor_user_id' => $actor->id,
            'note' => $note,
            'metadata' => ['folio' => $er->folio],
        ]);
    }

    private function commitBudget(ExpenseRequest $er): void
    {
        $budget = Budget::query()
            ->where('budgetable_type', 'region')
            ->where('budgetable_id', $er->user->region_id)
            ->first();

        if (! $budget) {
            return;
        }

        BudgetLedgerEntry::query()->create([
            'budget_id' => $budget->id,
            'entry_type' => BudgetLedgerEntryType::Commit,
            'amount_cents' => $er->approved_amount_cents ?? $er->requested_amount_cents,
            'source_type' => 'expense_request',
            'source_id' => $er->id,
        ]);
    }

    private function spendBudget(ExpenseRequest $er): void
    {
        $budget = Budget::query()
            ->where('budgetable_type', 'region')
            ->where('budgetable_id', $er->user->region_id)
            ->first();

        if (! $budget) {
            return;
        }

        $payment = $er->payments()->first();
        if (! $payment) {
            return;
        }

        BudgetLedgerEntry::query()->create([
            'budget_id' => $budget->id,
            'entry_type' => BudgetLedgerEntryType::Spend,
            'amount_cents' => $payment->amount_cents,
            'source_type' => 'payment',
            'source_id' => $payment->id,
        ]);
    }
}
