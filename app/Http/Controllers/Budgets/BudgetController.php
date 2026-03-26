<?php

namespace App\Http\Controllers\Budgets;

use App\Enums\BudgetLedgerEntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $user = $request->user();

        $budgets = Budget::query()
            ->with('budgetable')
            ->withCount('ledgerEntries')
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
            ->through(function (Budget $budget) use ($user): array {
                $row = $this->presentBudgetSummary($budget);
                $row['can_edit'] = $user !== null && $user->can('update', $budget);
                $row['can_delete'] = $user !== null
                    && $user->can('delete', $budget)
                    && $budget->ledger_entries_count === 0;

                return $row;
            });

        return Inertia::render('budgets/index', [
            'budgets' => $budgets,
            'filters' => [
                'search' => $request->query('search', ''),
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
        Budget::query()->create([
            'budgetable_type' => $request->string('budgetable_type')->toString(),
            'budgetable_id' => $request->integer('budgetable_id'),
            'period_starts_on' => $request->date('period_starts_on'),
            'period_ends_on' => $request->date('period_ends_on'),
            'amount_limit_cents' => $request->integer('amount_limit_cents'),
            'priority' => $request->filled('priority') ? $request->integer('priority') : null,
        ]);

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto creado.'));
    }

    public function edit(Budget $budget): Response
    {
        $this->authorize('update', $budget);

        $budget->load('budgetable');
        $budget->loadCount('ledgerEntries');

        return Inertia::render('budgets/edit', array_merge($this->budgetFormShared(), [
            'budget' => $this->presentBudgetForForm($budget),
            'can' => [
                'delete' => $budget->ledger_entries_count === 0
                    && (auth()->user()?->can('delete', $budget) ?? false),
            ],
        ]));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $budget->update([
            'budgetable_type' => $request->string('budgetable_type')->toString(),
            'budgetable_id' => $request->integer('budgetable_id'),
            'period_starts_on' => $request->date('period_starts_on'),
            'period_ends_on' => $request->date('period_ends_on'),
            'amount_limit_cents' => $request->integer('amount_limit_cents'),
            'priority' => $request->filled('priority') ? $request->integer('priority') : null,
        ]);

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto actualizado.'));
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        if ($budget->ledgerEntries()->exists()) {
            return redirect()
                ->route('budgets.index')
                ->withErrors([
                    'budget' => __('No se puede eliminar un presupuesto con movimientos en el ledger.'),
                ]);
        }

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Presupuesto eliminado.'));
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
        ];
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
}
