<?php

namespace App\Http\Controllers\Reports;

use App\Enums\DeliveryMethod;
use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Region;
use App\Models\ReportTemplate;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use App\Services\Reports\ExpenseAggregator;
use App\Services\Reports\PeriodPresetResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ExpenseAnalyticsController extends Controller
{
    public function index(
        Request $request,
        PeriodPresetResolver $resolver,
        ExpenseAggregator $aggregator,
    ): InertiaResponse {
        $this->authorizeAccess($request);

        $user = $request->user();

        // Optionally hydrate baseline filters from a template.
        $template = $this->resolveTemplate($request);
        $base = $template?->filters ?? [];

        $rawPeriod = $request->query('period', $base['period'] ?? null);
        $filters = $this->resolveFilters($request, $base);
        // If the caller passes only a custom date range without specifying
        // a preset, treat that as "custom" so the range is honoured.
        if (! $rawPeriod && ($filters['date_from'] || $filters['date_to'])) {
            $rawPeriod = 'custom';
        }
        $period = $rawPeriod ?: 'ytd';
        $compare = $request->boolean('compare', (bool) ($base['compare'] ?? false));
        $view = $request->query('view', $base['view'] ?? $template?->view ?? 'resumen');
        if (! in_array($view, ['resumen', 'pivote', 'detalle'], true)) {
            $view = 'resumen';
        }
        $groupBy = $request->query('group_by', $base['group_by'] ?? $template?->group_by ?? 'region');
        $granularity = $request->query('granularity');

        $resolved = $resolver->resolve(
            $period,
            $filters['date_from'] ?: null,
            $filters['date_to'] ?: null,
        );

        $range = ['start' => $resolved['start'], 'end' => $resolved['end'], 'granularity' => $resolved['granularity']];
        $prevRange = $compare
            ? ['start' => $resolved['prev_start'], 'end' => $resolved['prev_end'], 'granularity' => $resolved['granularity']]
            : null;

        $kpis = $aggregator->kpis($filters, $range, $prevRange);
        $sparklines = $aggregator->sparklines($filters, $range);
        $byStatus = $aggregator->byStatus($filters, $range);

        $payload = [
            'kpis' => $kpis,
            'sparklines' => $sparklines,
            'byStatus' => $byStatus,
            'templates' => $this->templatesPayload($user),
            'period' => [
                'id' => $resolved['id'],
                'label' => $resolved['label'],
                'range_label' => $resolved['range_label'],
                'start' => $resolved['start']->toIso8601String(),
                'end' => $resolved['end']->toIso8601String(),
                'prev_start' => $resolved['prev_start']->toIso8601String(),
                'prev_end' => $resolved['prev_end']->toIso8601String(),
                'granularity' => $resolved['granularity'],
            ],
            'period_presets' => $resolver->listPresets(),
            'view' => $view,
            'group_by' => $groupBy,
            'compare' => $compare,
            'filters' => $filters,
            'active_template_id' => $template?->id,
            'filter_options' => Inertia::lazy(fn () => $this->filterOptions()),
        ];

        if ($view === 'resumen') {
            $payload['timeSeries'] = $aggregator->timeSeries($filters, $range, $granularity);
            $payload['byRegion'] = $aggregator->byDimension($filters, $range, 'region');
            $payload['byConcept'] = $aggregator->byDimension($filters, $range, 'concepto');
            $payload['byUser'] = array_slice(
                $aggregator->byDimension($filters, $range, 'usuario'),
                0,
                6,
            );
        }

        if ($view === 'pivote') {
            $payload['byDimension'] = $aggregator->byDimension($filters, $range, $groupBy);
        }

        if ($view === 'detalle') {
            $payload['expenseRequests'] = $this->paginatedDetail($aggregator, $filters, $range);
        }

        return Inertia::render('reports/index', $payload);
    }

    private function resolveTemplate(Request $request): ?ReportTemplate
    {
        $id = $request->query('template_id');
        if (! $id) {
            return null;
        }

        $user = $request->user();

        return ReportTemplate::query()
            ->whereKey($id)
            ->where(function ($q) use ($user) {
                $q->where('is_built_in', true)
                    ->orWhere('is_shared', true)
                    ->orWhere('owner_user_id', $user?->id);
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, string>
     */
    private function resolveFilters(Request $request, array $base): array
    {
        $keys = [
            'search', 'status', 'region_id', 'state_id', 'user_id',
            'expense_concept_id', 'delivery_method', 'role_id',
            'date_from', 'date_to',
        ];

        $out = [];

        foreach ($keys as $k) {
            $value = $request->query($k, $base[$k] ?? '');
            $out[$k] = is_string($value) ? $value : (string) $value;
        }

        return $out;
    }

    private function paginatedDetail(ExpenseAggregator $aggregator, array $filters, array $range)
    {
        return $aggregator
            ->baseQuery($filters, $range)
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
    }

    private function templatesPayload(?User $user): array
    {
        $rows = ReportTemplate::query()
            ->where(function ($q) use ($user) {
                $q->where('is_built_in', true);
                if ($user !== null) {
                    $q->orWhere('owner_user_id', $user->id);
                }
                $q->orWhere('is_shared', true);
            })
            ->orderBy('is_built_in', 'desc')
            ->orderBy('name')
            ->get();

        return $rows->map(fn (ReportTemplate $t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'description' => $t->description,
            'icon' => $t->icon,
            'view' => $t->view,
            'group_by' => $t->group_by,
            'filters' => $t->filters,
            'is_built_in' => $t->is_built_in,
            'is_shared' => $t->is_shared,
            'is_owner' => $user !== null && $t->owner_user_id === $user->id,
        ])->all();
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->hasPermission('report.expenses.view'),
            403,
        );
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
            'regions' => Region::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])->all(),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'region_id'])
                ->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name, 'region_id' => (string) $s->region_id])->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->all(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])->all(),
            'expense_concepts' => ExpenseConcept::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'delivery_methods' => array_map(
                static fn (DeliveryMethod $d) => ['value' => $d->value, 'label' => $d->label()],
                DeliveryMethod::cases(),
            ),
        ];
    }
}
