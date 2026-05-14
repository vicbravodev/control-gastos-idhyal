<?php

namespace App\Services\Reports;

use App\Enums\DeliveryMethod;
use App\Enums\ExpenseRequestStatus;
use App\Models\ExpenseRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExpenseAggregator
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}|null  $prevRange
     * @return array<string, mixed>
     */
    public function kpis(array $filters, array $range, ?array $prevRange = null): array
    {
        $current = $this->kpiBlock($filters, $range);

        if ($prevRange === null) {
            return $current;
        }

        $prev = $this->kpiBlock($filters, $prevRange);

        foreach (['total_count', 'total_requested_cents', 'total_approved_cents', 'total_paid_cents'] as $key) {
            $current[$key.'_prev'] = $prev[$key];
            $current[$key.'_delta_pct'] = $this->deltaPct($prev[$key], $current[$key]);
        }

        $current['avg_approval_hours_prev'] = $prev['avg_approval_hours'];
        $current['avg_approval_hours_delta'] = $current['avg_approval_hours'] !== null && $prev['avg_approval_hours'] !== null
            ? round($current['avg_approval_hours'] - $prev['avg_approval_hours'], 1)
            : null;

        return $current;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return array<string, mixed>
     */
    private function kpiBlock(array $filters, array $range): array
    {
        $query = $this->baseQuery($filters, $range);

        $totalCount = (clone $query)->count();
        $totalRequested = (int) (clone $query)->sum('requested_amount_cents');
        $totalApproved = (int) (clone $query)->sum('approved_amount_cents');

        $totalPaid = (int) DB::table('payments')
            ->join('expense_requests', 'payments.expense_request_id', '=', 'expense_requests.id')
            ->whereBetween('expense_requests.created_at', [$range['start'], $range['end']])
            ->when(($filters['search'] ?? null), fn ($q, string $s) => $q->where('expense_requests.folio', 'like', "%{$s}%"))
            ->when(($filters['status'] ?? null), fn ($q, string $v) => $q->where('expense_requests.status', $v))
            ->when(($filters['expense_concept_id'] ?? null), fn ($q, string $v) => $q->where('expense_requests.expense_concept_id', $v))
            ->when(($filters['delivery_method'] ?? null), fn ($q, string $v) => $q->where('expense_requests.delivery_method', $v))
            ->when(($filters['user_id'] ?? null), fn ($q, string $v) => $q->where('expense_requests.user_id', $v))
            ->sum('payments.amount_cents');

        return [
            'total_count' => $totalCount,
            'total_requested_cents' => $totalRequested,
            'total_approved_cents' => $totalApproved,
            'total_paid_cents' => $totalPaid,
            'avg_approval_hours' => $this->avgApprovalHours($filters, $range),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    private function avgApprovalHours(array $filters, array $range): ?float
    {
        $approvedFlowStatuses = [
            ExpenseRequestStatus::Approved->value,
            ExpenseRequestStatus::PendingPayment->value,
            ExpenseRequestStatus::Paid->value,
            ExpenseRequestStatus::AwaitingExpenseReport->value,
            ExpenseRequestStatus::ExpenseReportInReview->value,
            ExpenseRequestStatus::ExpenseReportApproved->value,
            ExpenseRequestStatus::SettlementPending->value,
            ExpenseRequestStatus::Closed->value,
        ];

        $rows = DB::table('expense_requests')
            ->whereBetween('expense_requests.created_at', [$range['start'], $range['end']])
            ->whereIn('expense_requests.status', $approvedFlowStatuses)
            ->when(($filters['user_id'] ?? null), fn ($q, string $v) => $q->where('expense_requests.user_id', $v))
            ->join('expense_request_approvals', 'expense_request_approvals.expense_request_id', '=', 'expense_requests.id')
            ->whereNotNull('expense_request_approvals.acted_at')
            ->select('expense_requests.id', 'expense_requests.created_at', 'expense_request_approvals.acted_at')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $byRequest = [];
        foreach ($rows as $r) {
            $id = $r->id;
            $actedAt = CarbonImmutable::parse($r->acted_at);
            if (! isset($byRequest[$id]) || $actedAt > $byRequest[$id]['acted_at']) {
                $byRequest[$id] = [
                    'created_at' => CarbonImmutable::parse($r->created_at),
                    'acted_at' => $actedAt,
                ];
            }
        }

        $hours = array_map(
            fn (array $rec) => $rec['acted_at']->diffInSeconds($rec['created_at']) / 3600,
            $byRequest,
        );

        return round(array_sum($hours) / count($hours), 1);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return list<array{status: string, label: string, count: int, total_cents: int}>
     */
    public function byStatus(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->selectRaw('status, count(*) as c, sum(requested_amount_cents) as total_cents')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($r) => is_object($r->status) ? $r->status->value : $r->status);

        $out = [];

        foreach (ExpenseRequestStatus::cases() as $status) {
            $row = $rows->get($status->value);
            $out[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $row ? (int) $row->c : 0,
                'total_cents' => $row ? (int) $row->total_cents : 0,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, granularity?: string}  $range
     * @return list<array{bucket: string, label: string, count: int, solicitado_cents: int, aprobado_cents: int, pagado_cents: int}>
     */
    public function timeSeries(array $filters, array $range, ?string $granularity = null): array
    {
        $gran = $granularity ?? ($range['granularity'] ?? 'month');

        $buckets = $this->bucketsFor($range['start'], $range['end'], $gran);

        $requests = $this->baseQuery($filters, $range)
            ->select('id', 'created_at', 'requested_amount_cents', 'approved_amount_cents')
            ->get();

        $payments = DB::table('payments')
            ->join('expense_requests', 'payments.expense_request_id', '=', 'expense_requests.id')
            ->whereBetween('expense_requests.created_at', [$range['start'], $range['end']])
            ->select('expense_requests.created_at', 'payments.amount_cents')
            ->get();

        $byBucket = [];
        foreach ($buckets as $key => $label) {
            $byBucket[$key] = [
                'bucket' => $key,
                'label' => $label,
                'count' => 0,
                'solicitado_cents' => 0,
                'aprobado_cents' => 0,
                'pagado_cents' => 0,
            ];
        }

        foreach ($requests as $r) {
            $bucketKey = $this->bucketKeyFor(CarbonImmutable::parse($r->created_at), $gran);
            if (! isset($byBucket[$bucketKey])) {
                continue;
            }
            $byBucket[$bucketKey]['count']++;
            $byBucket[$bucketKey]['solicitado_cents'] += (int) $r->requested_amount_cents;
            $byBucket[$bucketKey]['aprobado_cents'] += (int) ($r->approved_amount_cents ?? 0);
        }

        foreach ($payments as $p) {
            $bucketKey = $this->bucketKeyFor(CarbonImmutable::parse($p->created_at), $gran);
            if (! isset($byBucket[$bucketKey])) {
                continue;
            }
            $byBucket[$bucketKey]['pagado_cents'] += (int) $p->amount_cents;
        }

        return array_values($byBucket);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return list<array<string, mixed>>
     */
    public function byDimension(array $filters, array $range, string $groupBy): array
    {
        return match ($groupBy) {
            'region' => $this->groupByRegion($filters, $range),
            'estado' => $this->groupByState($filters, $range),
            'usuario' => $this->groupByUser($filters, $range),
            'concepto' => $this->groupByConcept($filters, $range),
            'status' => $this->groupByStatus($filters, $range),
            'mes' => $this->groupByMonth($filters, $range),
            'entrega' => $this->groupByDelivery($filters, $range),
            'rol' => $this->groupByRole($filters, $range),
            default => $this->groupByRegion($filters, $range),
        };
    }

    /**
     * 10-point sparkline series per KPI metric. Buckets the range in equal slices
     * and aggregates in PHP — fast enough for typical YTD windows.
     *
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return array<string, list<int>>
     */
    public function sparklines(array $filters, array $range): array
    {
        $points = 10;
        $start = $range['start'];
        $end = $range['end'];

        $totalSeconds = max(1, $end->diffInSeconds($start));
        $stepSeconds = max(1, (int) floor($totalSeconds / $points));

        $boundaries = [];
        for ($i = 0; $i < $points; $i++) {
            $boundaries[] = [
                $start->copy()->addSeconds($i * $stepSeconds),
                $i === $points - 1
                    ? $end
                    : $start->copy()->addSeconds(($i + 1) * $stepSeconds)->subSecond(),
            ];
        }

        $requests = $this->baseQuery($filters, $range)
            ->select('created_at', 'requested_amount_cents', 'approved_amount_cents')
            ->get();

        $payments = DB::table('payments')
            ->join('expense_requests', 'payments.expense_request_id', '=', 'expense_requests.id')
            ->whereBetween('expense_requests.created_at', [$range['start'], $range['end']])
            ->select('expense_requests.created_at', 'payments.amount_cents')
            ->get();

        $count = array_fill(0, $points, 0);
        $solicitado = array_fill(0, $points, 0);
        $aprobado = array_fill(0, $points, 0);
        $pagado = array_fill(0, $points, 0);

        foreach ($requests as $r) {
            $createdAt = CarbonImmutable::parse($r->created_at);
            $idx = $this->bucketIndex($createdAt, $boundaries);
            if ($idx === null) {
                continue;
            }
            $count[$idx]++;
            $solicitado[$idx] += (int) $r->requested_amount_cents;
            $aprobado[$idx] += (int) ($r->approved_amount_cents ?? 0);
        }

        foreach ($payments as $p) {
            $createdAt = CarbonImmutable::parse($p->created_at);
            $idx = $this->bucketIndex($createdAt, $boundaries);
            if ($idx === null) {
                continue;
            }
            $pagado[$idx] += (int) $p->amount_cents;
        }

        return [
            'count' => $count,
            'solicitado' => $solicitado,
            'aprobado' => $aprobado,
            'pagado' => $pagado,
        ];
    }

    /**
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $boundaries
     */
    private function bucketIndex(CarbonImmutable $when, array $boundaries): ?int
    {
        foreach ($boundaries as $idx => [$bStart, $bEnd]) {
            if ($when >= $bStart && $when <= $bEnd) {
                return $idx;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return Builder<ExpenseRequest>
     */
    public function baseQuery(array $filters, array $range): Builder
    {
        return ExpenseRequest::query()
            ->whereBetween('expense_requests.created_at', [$range['start'], $range['end']])
            ->when(($filters['search'] ?? null), fn (Builder $q, string $s) => $q->where('expense_requests.folio', 'like', "%{$s}%"))
            ->when(($filters['status'] ?? null), fn (Builder $q, string $v) => $q->where('expense_requests.status', $v))
            ->when(($filters['expense_concept_id'] ?? null), fn (Builder $q, string $v) => $q->where('expense_requests.expense_concept_id', $v))
            ->when(($filters['delivery_method'] ?? null), fn (Builder $q, string $v) => $q->where('expense_requests.delivery_method', $v))
            ->when(($filters['user_id'] ?? null), fn (Builder $q, string $v) => $q->where('expense_requests.user_id', $v))
            ->when(($filters['region_id'] ?? null), fn (Builder $q, string $v) => $q->whereHas('user', fn (Builder $uq) => $uq->where('region_id', $v)))
            ->when(($filters['state_id'] ?? null), fn (Builder $q, string $v) => $q->whereHas('user', fn (Builder $uq) => $uq->where('state_id', $v)))
            ->when(($filters['role_id'] ?? null), fn (Builder $q, string $v) => $q->whereHas('user', fn (Builder $uq) => $uq->where('role_id', $v)));
    }

    private function deltaPct(int|float|null $prev, int|float|null $current): ?float
    {
        if ($prev === null || $current === null) {
            return null;
        }

        if ((float) $prev === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $prev) / $prev) * 100, 1);
    }

    /**
     * @return array<string, string> bucket key → human label
     */
    private function bucketsFor(CarbonImmutable $start, CarbonImmutable $end, string $granularity): array
    {
        $monthsShort = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $out = [];

        if ($granularity === 'month') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor <= $end) {
                $out[$cursor->format('Y-m')] = $monthsShort[$cursor->month - 1];
                $cursor = $cursor->addMonth();
            }

            return $out;
        }

        if ($granularity === 'week') {
            $cursor = $start->copy()->startOfWeek();
            while ($cursor <= $end) {
                $out[$cursor->format('Y-m-d')] = $cursor->format('d').' '.$monthsShort[$cursor->month - 1];
                $cursor = $cursor->addWeek();
            }

            return $out;
        }

        $cursor = $start->copy()->startOfDay();
        while ($cursor <= $end) {
            $out[$cursor->format('Y-m-d')] = $cursor->format('d').' '.$monthsShort[$cursor->month - 1];
            $cursor = $cursor->addDay();
        }

        return $out;
    }

    private function bucketKeyFor(CarbonImmutable $when, string $granularity): string
    {
        return match ($granularity) {
            'month' => $when->format('Y-m'),
            'week' => $when->copy()->startOfWeek()->format('Y-m-d'),
            'day' => $when->copy()->startOfDay()->format('Y-m-d'),
            default => $when->format('Y-m'),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    private function groupByRegion(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->join('users', 'expense_requests.user_id', '=', 'users.id')
            ->leftJoin('regions', 'users.region_id', '=', 'regions.id')
            ->selectRaw('regions.id as gid, regions.name as gname, count(*) as c, sum(expense_requests.requested_amount_cents) as sol, sum(expense_requests.approved_amount_cents) as apr')
            ->groupBy('regions.id', 'regions.name')
            ->orderByDesc('sol')
            ->get();

        return $this->shapeDimensionRows(
            $rows,
            fn ($r) => $r->gname ?: 'Sin región',
            fn ($r) => 'Región',
        );
    }

    private function groupByState(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->join('users', 'expense_requests.user_id', '=', 'users.id')
            ->leftJoin('states', 'users.state_id', '=', 'states.id')
            ->leftJoin('regions', 'states.region_id', '=', 'regions.id')
            ->selectRaw('states.id as gid, states.name as gname, regions.name as gmeta, count(*) as c, sum(expense_requests.requested_amount_cents) as sol, sum(expense_requests.approved_amount_cents) as apr')
            ->groupBy('states.id', 'states.name', 'regions.name')
            ->orderByDesc('sol')
            ->get();

        return $this->shapeDimensionRows(
            $rows,
            fn ($r) => $r->gname ?: 'Sin estado',
            fn ($r) => $r->gmeta ?: 'Estado (geo)',
        );
    }

    private function groupByUser(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->join('users', 'expense_requests.user_id', '=', 'users.id')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('regions', 'users.region_id', '=', 'regions.id')
            ->selectRaw('users.id as gid, users.name as gname, roles.name as gmeta, regions.name as gmeta2, count(*) as c, sum(expense_requests.requested_amount_cents) as sol, sum(expense_requests.approved_amount_cents) as apr')
            ->groupBy('users.id', 'users.name', 'roles.name', 'regions.name')
            ->orderByDesc('sol')
            ->get();

        return $this->shapeDimensionRows(
            $rows,
            fn ($r) => (string) $r->gname,
            fn ($r) => trim(($r->gmeta ?: 'Sin rol').' · '.($r->gmeta2 ?: '—'), ' ·'),
        );
    }

    private function groupByConcept(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->leftJoin('expense_concepts', 'expense_requests.expense_concept_id', '=', 'expense_concepts.id')
            ->selectRaw('expense_concepts.id as gid, expense_concepts.name as gname, count(*) as c, sum(expense_requests.requested_amount_cents) as sol, sum(expense_requests.approved_amount_cents) as apr')
            ->groupBy('expense_concepts.id', 'expense_concepts.name')
            ->orderByDesc('sol')
            ->get();

        return $this->shapeDimensionRows(
            $rows,
            fn ($r) => $r->gname ?: 'Sin concepto',
            fn ($r) => 'Concepto',
        );
    }

    private function groupByStatus(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->selectRaw('status as gname, count(*) as c, sum(requested_amount_cents) as sol, sum(approved_amount_cents) as apr')
            ->groupBy('status')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $key = is_object($r->gname) ? $r->gname->value : $r->gname;
            $statusEnum = ExpenseRequestStatus::tryFrom($key);
            $out[] = [
                'key' => $statusEnum?->label() ?? (string) $key,
                'meta' => 'Estado',
                'count' => (int) $r->c,
                'solicitado_cents' => (int) $r->sol,
                'aprobado_cents' => (int) $r->apr,
                'pagado_cents' => 0,
                'pend_cents' => max(0, (int) $r->sol - (int) $r->apr),
            ];
        }

        usort($out, fn ($a, $b) => $b['solicitado_cents'] <=> $a['solicitado_cents']);

        return $out;
    }

    private function groupByMonth(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->select('created_at', 'requested_amount_cents', 'approved_amount_cents')
            ->get();

        $monthsShort = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $buckets = [];

        foreach ($rows as $r) {
            $when = CarbonImmutable::parse($r->created_at);
            $key = $when->format('Y-m');
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'key' => $monthsShort[$when->month - 1].' '.$when->year,
                    'meta' => 'Mes',
                    'count' => 0,
                    'solicitado_cents' => 0,
                    'aprobado_cents' => 0,
                    'pagado_cents' => 0,
                    'pend_cents' => 0,
                ];
            }
            $buckets[$key]['count']++;
            $buckets[$key]['solicitado_cents'] += (int) $r->requested_amount_cents;
            $buckets[$key]['aprobado_cents'] += (int) ($r->approved_amount_cents ?? 0);
        }

        foreach ($buckets as &$b) {
            $b['pend_cents'] = max(0, $b['solicitado_cents'] - $b['aprobado_cents']);
        }
        unset($b);

        ksort($buckets);

        return array_values($buckets);
    }

    private function groupByDelivery(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->selectRaw('delivery_method as gname, count(*) as c, sum(requested_amount_cents) as sol, sum(approved_amount_cents) as apr')
            ->groupBy('delivery_method')
            ->orderByDesc('sol')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $key = is_object($r->gname) ? $r->gname->value : $r->gname;
            $method = DeliveryMethod::tryFrom($key);
            $out[] = [
                'key' => $method?->label() ?? (string) $key,
                'meta' => 'Forma de entrega',
                'count' => (int) $r->c,
                'solicitado_cents' => (int) $r->sol,
                'aprobado_cents' => (int) $r->apr,
                'pagado_cents' => 0,
                'pend_cents' => max(0, (int) $r->sol - (int) $r->apr),
            ];
        }

        return $out;
    }

    private function groupByRole(array $filters, array $range): array
    {
        $rows = $this->baseQuery($filters, $range)
            ->join('users', 'expense_requests.user_id', '=', 'users.id')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->selectRaw('roles.id as gid, roles.name as gname, count(*) as c, sum(expense_requests.requested_amount_cents) as sol, sum(expense_requests.approved_amount_cents) as apr')
            ->groupBy('roles.id', 'roles.name')
            ->orderByDesc('sol')
            ->get();

        return $this->shapeDimensionRows(
            $rows,
            fn ($r) => $r->gname ?: 'Sin rol',
            fn ($r) => 'Rol',
        );
    }

    /**
     * @param  iterable<object>  $rows
     * @param  callable(object): string  $keyOf
     * @param  callable(object): string  $metaOf
     * @return list<array<string, mixed>>
     */
    private function shapeDimensionRows(iterable $rows, callable $keyOf, callable $metaOf): array
    {
        $out = [];
        foreach ($rows as $r) {
            $sol = (int) $r->sol;
            $apr = (int) $r->apr;
            $out[] = [
                'key' => $keyOf($r),
                'meta' => $metaOf($r),
                'count' => (int) $r->c,
                'solicitado_cents' => $sol,
                'aprobado_cents' => $apr,
                'pagado_cents' => 0,
                'pend_cents' => max(0, $sol - $apr),
            ];
        }

        return $out;
    }
}
