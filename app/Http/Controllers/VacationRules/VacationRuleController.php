<?php

namespace App\Http\Controllers\VacationRules;

use App\Http\Controllers\Controller;
use App\Http\Requests\VacationRules\StoreVacationRuleRequest;
use App\Http\Requests\VacationRules\UpdateVacationRuleRequest;
use App\Models\VacationRule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VacationRuleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', VacationRule::class);

        $rules = VacationRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VacationRule $r): array => $this->presentRuleRow($r));

        return Inertia::render('vacation-rules/index', [
            'rules' => $rules,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VacationRule::class);

        return Inertia::render('vacation-rules/create');
    }

    public function store(StoreVacationRuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        VacationRule::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'min_years_service' => $validated['min_years_service'],
            'max_years_service' => $validated['max_years_service'] ?? null,
            'days_granted_per_year' => $validated['days_granted_per_year'],
            'max_days_per_request' => $validated['max_days_per_request'] ?? null,
            'max_days_per_month' => $validated['max_days_per_month'] ?? null,
            'max_days_per_quarter' => $validated['max_days_per_quarter'] ?? null,
            'max_days_per_year' => $validated['max_days_per_year'] ?? null,
            'blackout_dates' => $this->parseBlackoutDates($validated['blackout_dates'] ?? null),
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()
            ->route('vacation-rules.index')
            ->with('status', __('Regla de vacaciones creada.'));
    }

    public function edit(VacationRule $vacation_rule): Response
    {
        $this->authorize('update', $vacation_rule);

        return Inertia::render('vacation-rules/edit', [
            'rule' => $this->presentRuleForm($vacation_rule),
        ]);
    }

    public function update(UpdateVacationRuleRequest $request, VacationRule $vacation_rule): RedirectResponse
    {
        $validated = $request->validated();

        $vacation_rule->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'min_years_service' => $validated['min_years_service'],
            'max_years_service' => $validated['max_years_service'] ?? null,
            'days_granted_per_year' => $validated['days_granted_per_year'],
            'max_days_per_request' => $validated['max_days_per_request'] ?? null,
            'max_days_per_month' => $validated['max_days_per_month'] ?? null,
            'max_days_per_quarter' => $validated['max_days_per_quarter'] ?? null,
            'max_days_per_year' => $validated['max_days_per_year'] ?? null,
            'blackout_dates' => $this->parseBlackoutDates($validated['blackout_dates'] ?? null),
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()
            ->route('vacation-rules.index')
            ->with('status', __('Regla actualizada.'));
    }

    public function destroy(VacationRule $vacation_rule): RedirectResponse
    {
        $this->authorize('delete', $vacation_rule);

        $vacation_rule->delete();

        return redirect()
            ->route('vacation-rules.index')
            ->with('status', __('Regla eliminada.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRuleRow(VacationRule $r): array
    {
        return [
            'id' => $r->id,
            'code' => $r->code,
            'name' => $r->name,
            'min_years_service' => (float) $r->min_years_service,
            'max_years_service' => $r->max_years_service !== null ? (float) $r->max_years_service : null,
            'days_granted_per_year' => $r->days_granted_per_year,
            'max_days_per_request' => $r->max_days_per_request,
            'sort_order' => $r->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRuleForm(VacationRule $r): array
    {
        return [
            'id' => $r->id,
            'code' => $r->code,
            'name' => $r->name,
            'min_years_service' => (string) $r->min_years_service,
            'max_years_service' => $r->max_years_service !== null ? (string) $r->max_years_service : '',
            'days_granted_per_year' => (string) $r->days_granted_per_year,
            'max_days_per_request' => $r->max_days_per_request !== null ? (string) $r->max_days_per_request : '',
            'max_days_per_month' => $r->max_days_per_month !== null ? (string) $r->max_days_per_month : '',
            'max_days_per_quarter' => $r->max_days_per_quarter !== null ? (string) $r->max_days_per_quarter : '',
            'max_days_per_year' => $r->max_days_per_year !== null ? (string) $r->max_days_per_year : '',
            'blackout_dates' => $r->blackout_dates === null || $r->blackout_dates === []
                ? ''
                : (string) json_encode($r->blackout_dates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'sort_order' => (string) $r->sort_order,
        ];
    }

    /**
     * @return list<mixed>|null
     */
    private function parseBlackoutDates(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
