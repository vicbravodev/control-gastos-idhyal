<?php

namespace App\Http\Controllers\Budgets;

use App\Enums\BudgetLedgerEntryType;
use App\Enums\BudgetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgets\CancelBudgetRequest;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use App\Services\Budgets\BudgetAuditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetAuditor $auditor) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $user = $request->user();
        $statusFilter = (string) $request->query('status', 'active');
        if (! in_array($statusFilter, ['active', 'cancelled', 'all'], true)) {
            $statusFilter = 'active';
        }

        $budgets = Budget::query()
            ->with('budgetable')
            ->withCount('ledgerEntries')
            ->when($statusFilter === 'active', fn ($q) => $q->where('status', BudgetStatus::Active->value))
            ->when($statusFilter === 'cancelled', fn ($q) => $q->where('status', BudgetStatus::Cancelled->value))
            ->when($request->query('search'), function ($q, $search): void {
                $like = '%'.(string) $search.'%';
                $q->where(function ($w) use ($like): void {
                    $w->where(function ($s) use ($like): void {
                        $s->where('budgetable_type', 'user')
                            ->whereHas('budgetable', fn ($bq) => $bq->where('name', 'like', $like));
                    })->orWhere(function ($s) use ($like): void {
                        $s->where('budgetable_type', 'role')
                            ->whereHas('budgetable', fn ($bq) => $bq->where('name', 'like', $like));
                    })->orWhere(function ($s) use ($like): void {
                        $s->where('budgetable_type', 'state')
                            ->whereHas('budgetable', fn ($bq) => $bq->where('name', 'like', $like));
                    })->orWhere(function ($s) use ($like): void {
                        $s->where('budgetable_type', 'region')
                            ->whereHas('budgetable', fn ($bq) => $bq->where(fn ($inner) => $inner->where('name', 'like', $like)->orWhere('code', 'like', $like)));
                    });
                });
            })
            ->orderByDesc('period_ends_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Budget $budget) use ($user): array {
                $row = $this->presentBudgetSummary($budget);
                $row['can_edit'] = $user !== null && $user->can('update', $budget);
                $row['can_cancel'] = $user !== null && $user->can('cancel', $budget);

                return $row;
            });

        return Inertia::render('budgets/index', [
            'budgets' => $budgets,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $statusFilter,
            ],
            'can' => [
                'create' => $user?->can('create', Budget::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Budget::class);

        return Inertia::render('budgets/create', $this->budgetFormShared());
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = Budget::query()->create([
            'budgetable_type' => $request->string('budgetable_type')->toString(),
            'budgetable_id' => $request->integer('budgetable_id'),
            'period_starts_on' => $request->date('period_starts_on'),
            'period_ends_on' => $request->date('period_ends_on'),
            'amount_limit_cents' => $request->integer('amount_limit_cents'),
            'priority' => $request->filled('priority') ? $request->integer('priority') : null,
            'status' => BudgetStatus::Active,
        ]);

        $this->auditor->recordCreated($budget, $request->user());

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto creado.'));
    }

    public function edit(Budget $budget): Response
    {
        $this->authorize('update', $budget);

        $budget->load(['budgetable', 'cancelledBy', 'audits.actor']);
        $budget->loadCount('ledgerEntries');

        return Inertia::render('budgets/edit', array_merge($this->budgetFormShared(), [
            'budget' => $this->presentBudgetForForm($budget),
            'audits' => $this->presentAudits($budget),
            'can' => [
                'update' => auth()->user()?->can('update', $budget) ?? false,
                'cancel' => auth()->user()?->can('cancel', $budget) ?? false,
            ],
        ]));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        if (! $budget->isActive()) {
            return redirect()
                ->route('budgets.edit', $budget)
                ->withErrors([
                    'budget' => __('No se puede editar un presupuesto cancelado.'),
                ]);
        }

        $previousAmount = (int) $budget->amount_limit_cents;
        $previousScope = [
            'budgetable_type' => $budget->budgetable_type,
            'budgetable_id' => $budget->budgetable_id,
            'period_starts_on' => $budget->period_starts_on?->toDateString(),
            'period_ends_on' => $budget->period_ends_on?->toDateString(),
            'priority' => $budget->priority,
        ];

        $budget->update([
            'budgetable_type' => $request->string('budgetable_type')->toString(),
            'budgetable_id' => $request->integer('budgetable_id'),
            'period_starts_on' => $request->date('period_starts_on'),
            'period_ends_on' => $request->date('period_ends_on'),
            'amount_limit_cents' => $request->integer('amount_limit_cents'),
            'priority' => $request->filled('priority') ? $request->integer('priority') : null,
        ]);

        $newAmount = (int) $budget->amount_limit_cents;
        if ($newAmount !== $previousAmount) {
            $this->auditor->recordAmountChanged($budget, $previousAmount, $newAmount, null, $request->user());
        }

        $newScope = [
            'budgetable_type' => $budget->budgetable_type,
            'budgetable_id' => $budget->budgetable_id,
            'period_starts_on' => $budget->period_starts_on?->toDateString(),
            'period_ends_on' => $budget->period_ends_on?->toDateString(),
            'priority' => $budget->priority,
        ];
        if ($this->diff($previousScope, $newScope) !== []) {
            $this->auditor->recordScopeChanged(
                $budget,
                $previousScope,
                $newScope,
                null,
                $request->user(),
            );
        }

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto actualizado.'));
    }

    public function cancel(CancelBudgetRequest $request, Budget $budget): RedirectResponse
    {
        if (! $budget->isActive()) {
            return redirect()
                ->route('budgets.edit', $budget)
                ->withErrors([
                    'reason' => __('El presupuesto ya está cancelado.'),
                ]);
        }

        $reason = $request->string('reason')->toString();
        $previousStatus = $budget->status;

        $budget->update([
            'status' => BudgetStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
            'cancellation_reason' => $reason,
        ]);

        $this->auditor->recordStatusChanged(
            $budget,
            $previousStatus,
            BudgetStatus::Cancelled,
            $reason,
            $request->user(),
        );

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto cancelado.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetFormShared(): array
    {
        return [
            'budgetableTypes' => [
                ['value' => 'user', 'label' => 'Usuario'],
                ['value' => 'role', 'label' => 'Rol'],
                ['value' => 'state', 'label' => 'Estado'],
                ['value' => 'region', 'label' => 'Región'],
            ],
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBudgetForForm(Budget $budget): array
    {
        return [
            'id' => $budget->id,
            'budgetable_type' => $budget->budgetable_type,
            'budgetable_id' => $budget->budgetable_id,
            'period_starts_on' => $budget->period_starts_on?->toDateString(),
            'period_ends_on' => $budget->period_ends_on?->toDateString(),
            'amount_limit_cents' => (int) $budget->amount_limit_cents,
            'priority' => $budget->priority,
            'status' => $budget->status->value,
            'status_label' => $budget->status->label(),
            'cancelled_at' => $budget->cancelled_at?->toIso8601String(),
            'cancelled_by_name' => $budget->cancelledBy?->name,
            'cancellation_reason' => $budget->cancellation_reason,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentAudits(Budget $budget): array
    {
        return $budget->audits
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($audit): array => [
                'id' => $audit->id,
                'event' => $audit->event,
                'changes' => $audit->changes,
                'reason' => $audit->reason,
                'actor_name' => $audit->actor?->name,
                'created_at' => $audit->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBudgetSummary(Budget $budget): array
    {
        $commitTotal = (int) $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->sum('amount_cents');

        $commitIds = $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->pluck('id');

        $reversedCommit = (int) $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Reverse)
            ->whereIn('reverses_ledger_entry_id', $commitIds)
            ->sum('amount_cents');

        $spendTotal = (int) $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Spend)
            ->sum('amount_cents');

        $spendIds = $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Spend)
            ->pluck('id');

        $reversedSpend = (int) $budget->ledgerEntries()
            ->where('entry_type', BudgetLedgerEntryType::Reverse)
            ->whereIn('reverses_ledger_entry_id', $spendIds)
            ->sum('amount_cents');

        $netCommitted = max(0, $commitTotal - $reversedCommit);
        $netSpent = max(0, $spendTotal - $reversedSpend);

        return [
            'id' => $budget->id,
            'period_starts_on' => $budget->period_starts_on?->toDateString(),
            'period_ends_on' => $budget->period_ends_on?->toDateString(),
            'amount_limit_cents' => (int) $budget->amount_limit_cents,
            'priority' => $budget->priority,
            'scope_kind' => $budget->budgetable_type,
            'scope_label' => $this->budgetableLabel($budget),
            'committed_cents' => $netCommitted,
            'spent_cents' => $netSpent,
            'remaining_after_spend_cents' => max(0, (int) $budget->amount_limit_cents - $netSpent),
            'status' => $budget->status->value,
            'status_label' => $budget->status->label(),
        ];
    }

    private function budgetableLabel(Budget $budget): string
    {
        $model = $budget->budgetable;
        if ($model === null) {
            return '(sin asignar)';
        }

        if ($model instanceof User) {
            return $model->name;
        }

        if ($model instanceof Role) {
            return $model->name;
        }

        if ($model instanceof Region) {
            return $model->name ?? $model->code ?? 'Región #'.$model->id;
        }

        if ($model instanceof State) {
            return $model->name;
        }

        return 'ID '.$model->getKey();
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    private function diff(array $a, array $b): array
    {
        $diff = [];
        foreach ($b as $key => $value) {
            if (($a[$key] ?? null) !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }
}
