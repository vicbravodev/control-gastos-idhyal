<?php

namespace App\Http\Controllers\Reports;

use App\Enums\DeliveryMethod;
use App\Enums\ExpenseRequestStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Region;
use App\Models\State;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ExpenseAnalyticsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorizeAccess($request);

        $query = $this->buildFilteredQuery($request);

        $summaryQuery = (clone $query);

        $summary = [
            'total_count' => $summaryQuery->count(),
            'total_requested_cents' => (int) (clone $summaryQuery)->sum('requested_amount_cents'),
            'total_approved_cents' => (int) (clone $summaryQuery)->sum('approved_amount_cents'),
            'total_paid_cents' => (int) (clone $summaryQuery)
                ->whereHas('payments')
                ->get()
                ->flatMap->payments
                ->sum('amount_cents'),
            'by_status' => (clone $summaryQuery)
                ->selectRaw('status, count(*) as count, sum(requested_amount_cents) as total_cents')
                ->groupBy('status')
                ->get()
                ->map(fn ($row) => [
                    'status' => $row->status->value,
                    'count' => (int) $row->count,
                    'total_cents' => (int) $row->total_cents,
                ])
                ->values()
                ->all(),
        ];

        $expenseRequests = $query
            ->with(['user.region', 'user.state', 'user.role', 'expenseConcept', 'payments'])
            ->latest()
            ->paginate(20)
            ->through(fn (ExpenseRequest $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'status' => $r->status->value,
                'requested_amount_cents' => $r->requested_amount_cents,
                'approved_amount_cents' => $r->approved_amount_cents,
                'paid_amount_cents' => $r->payments->sum('amount_cents'),
                'concept_label' => $r->conceptLabel(),
                'concept_description' => $r->concept_description,
                'delivery_method' => $r->delivery_method->value,
                'user_name' => $r->user->name,
                'user_role' => $r->user->role?->name,
                'region_name' => $r->user->region?->name,
                'state_name' => $r->user->state?->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return Inertia::render('reports/index', [
            'summary' => $summary,
            'expenseRequests' => $expenseRequests,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
                'region_id' => $request->query('region_id', ''),
                'state_id' => $request->query('state_id', ''),
                'user_id' => $request->query('user_id', ''),
                'expense_concept_id' => $request->query('expense_concept_id', ''),
                'delivery_method' => $request->query('delivery_method', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
            ],
            'filter_options' => Inertia::lazy(fn () => $this->filterOptions()),
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorizeAccess($request);

        $query = $this->buildFilteredQuery($request);

        $rows = $query
            ->with(['user.region', 'user.state', 'user.role', 'expenseConcept', 'payments'])
            ->latest()
            ->limit(500)
            ->get();

        $summaryQuery = $this->buildFilteredQuery($request);

        $summary = [
            'total_count' => $summaryQuery->count(),
            'total_requested_cents' => (int) (clone $summaryQuery)->sum('requested_amount_cents'),
            'total_approved_cents' => (int) (clone $summaryQuery)->sum('approved_amount_cents'),
        ];

        $activeFiltersLabels = $this->activeFilterLabels($request);

        $pdf = Pdf::loadView('pdf.expense-analytics-report', [
            'rows' => $rows,
            'summary' => $summary,
            'activeFilters' => $activeFiltersLabels,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('reporte-gastos-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && (
                $user->hasRole(RoleSlug::Contabilidad) ||
                $user->hasRole(RoleSlug::SuperAdmin)
            ),
            403,
        );
    }

    /**
     * @return Builder<ExpenseRequest>
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        return ExpenseRequest::query()
            ->when($request->query('search'), fn (Builder $q, string $search) => $q->where('folio', 'like', "%{$search}%"))
            ->when($request->query('status'), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->query('expense_concept_id'), fn (Builder $q, string $id) => $q->where('expense_concept_id', $id))
            ->when($request->query('delivery_method'), fn (Builder $q, string $method) => $q->where('delivery_method', $method))
            ->when($request->query('date_from'), fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))
            ->when($request->query('user_id'), fn (Builder $q, string $userId) => $q->where('user_id', $userId))
            ->when($request->query('region_id'), fn (Builder $q, string $regionId) => $q->whereHas('user', fn (Builder $uq) => $uq->where('region_id', $regionId)))
            ->when($request->query('state_id'), fn (Builder $q, string $stateId) => $q->whereHas('user', fn (Builder $uq) => $uq->where('state_id', $stateId)));
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'statuses' => array_map(
                static fn (ExpenseRequestStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                ExpenseRequestStatus::cases(),
            ),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])->all(),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'region_id'])->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name, 'region_id' => (string) $s->region_id])->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->all(),
            'expense_concepts' => ExpenseConcept::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'delivery_methods' => array_map(
                static fn (DeliveryMethod $d) => ['value' => $d->value, 'label' => $d->label()],
                DeliveryMethod::cases(),
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function activeFilterLabels(Request $request): array
    {
        $labels = [];

        if ($request->filled('date_from')) {
            $labels[] = 'Desde: ' . $request->query('date_from');
        }
        if ($request->filled('date_to')) {
            $labels[] = 'Hasta: ' . $request->query('date_to');
        }
        if ($request->filled('status')) {
            $status = ExpenseRequestStatus::tryFrom($request->query('status'));
            $labels[] = 'Estado: ' . ($status?->label() ?? $request->query('status'));
        }
        if ($request->filled('region_id')) {
            $region = Region::query()->find($request->query('region_id'));
            $labels[] = 'Región: ' . ($region?->name ?? $request->query('region_id'));
        }
        if ($request->filled('state_id')) {
            $state = State::query()->find($request->query('state_id'));
            $labels[] = 'Estado: ' . ($state?->name ?? $request->query('state_id'));
        }
        if ($request->filled('user_id')) {
            $user = User::query()->find($request->query('user_id'));
            $labels[] = 'Usuario: ' . ($user?->name ?? $request->query('user_id'));
        }
        if ($request->filled('expense_concept_id')) {
            $concept = ExpenseConcept::query()->find($request->query('expense_concept_id'));
            $labels[] = 'Concepto: ' . ($concept?->name ?? $request->query('expense_concept_id'));
        }
        if ($request->filled('delivery_method')) {
            $method = DeliveryMethod::tryFrom($request->query('delivery_method'));
            $labels[] = 'Forma de entrega: ' . ($method?->label() ?? $request->query('delivery_method'));
        }
        if ($request->filled('search')) {
            $labels[] = 'Búsqueda: ' . $request->query('search');
        }

        return $labels;
    }
}
