<?php

namespace App\Http\Controllers\ExpenseConcepts;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseConcepts\StoreExpenseConceptRequest;
use App\Http\Requests\ExpenseConcepts\UpdateExpenseConceptRequest;
use App\Models\ExpenseConcept;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseConceptController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ExpenseConcept::class);

        $concepts = ExpenseConcept::query()
            ->withCount('expenseRequests')
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseConcept $c): array => $this->presentRow($c));

        return Inertia::render('expense-concepts/index', [
            'concepts' => $concepts,
            'filters' => [
                'search' => $request->query('search', ''),
                'active' => $request->query('active', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ExpenseConcept::class);

        return Inertia::render('expense-concepts/create');
    }

    public function store(StoreExpenseConceptRequest $request): RedirectResponse
    {
        ExpenseConcept::query()->create([
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return redirect()
            ->route('expense-concepts.index')
            ->with('status', __('Concepto creado.'));
    }

    public function edit(ExpenseConcept $expense_concept): Response
    {
        $this->authorize('update', $expense_concept);

        $expense_concept->loadCount('expenseRequests');

        return Inertia::render('expense-concepts/edit', [
            'concept' => $this->presentRow($expense_concept),
            'can' => [
                'delete' => auth()->user()?->can('delete', $expense_concept) ?? false,
            ],
        ]);
    }

    public function update(UpdateExpenseConceptRequest $request, ExpenseConcept $expense_concept): RedirectResponse
    {
        $validated = $request->validated();

        $expense_concept->update([
            'name' => $validated['name'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('expense-concepts.index')
            ->with('status', __('Concepto actualizado.'));
    }

    public function destroy(ExpenseConcept $expense_concept): RedirectResponse
    {
        $this->authorize('delete', $expense_concept);

        $expense_concept->delete();

        return redirect()
            ->route('expense-concepts.index')
            ->with('status', __('Concepto eliminado.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(ExpenseConcept $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'is_active' => $c->is_active,
            'sort_order' => $c->sort_order,
            'expense_requests_count' => (int) ($c->expense_requests_count ?? $c->expenseRequests()->count()),
        ];
    }
}
