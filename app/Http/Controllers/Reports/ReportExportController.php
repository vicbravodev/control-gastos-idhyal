<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseRequest;
use App\Services\Reports\ExpenseAggregator;
use App\Services\Reports\PeriodPresetResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $format,
        PeriodPresetResolver $resolver,
        ExpenseAggregator $aggregator,
    ): \Illuminate\Http\Response|StreamedResponse {
        abort_unless(in_array($format, ['pdf', 'csv'], true), 404);

        $user = $request->user();
        abort_unless($user !== null && $user->hasPermission('report.expenses.view'), 403);

        $filters = $this->resolveFilters($request);
        $resolved = $resolver->resolve(
            $request->query('period', 'ytd'),
            $filters['date_from'] ?: null,
            $filters['date_to'] ?: null,
        );

        $range = ['start' => $resolved['start'], 'end' => $resolved['end']];

        if ($format === 'csv') {
            return $this->csv($aggregator, $filters, $range, $resolved['range_label']);
        }

        return $this->pdf($aggregator, $filters, $range, $resolved['range_label']);
    }

    /**
     * @param  array<string, string>  $filters
     * @param  array{start: \Carbon\CarbonImmutable, end: \Carbon\CarbonImmutable}  $range
     */
    private function pdf(ExpenseAggregator $aggregator, array $filters, array $range, string $rangeLabel): \Illuminate\Http\Response
    {
        $rows = $aggregator
            ->baseQuery($filters, $range)
            ->with(['user.region', 'user.state', 'user.role', 'expenseConcept', 'payments'])
            ->latest()
            ->limit(500)
            ->get();

        $kpis = $aggregator->kpis($filters, $range);

        $pdf = Pdf::loadView('pdf.expense-analytics-report', [
            'rows' => $rows,
            'summary' => [
                'total_count' => $kpis['total_count'],
                'total_requested_cents' => $kpis['total_requested_cents'],
                'total_approved_cents' => $kpis['total_approved_cents'],
            ],
            'activeFilters' => $this->labelFilters($filters, $rangeLabel),
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('reporte-gastos-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * @param  array<string, string>  $filters
     * @param  array{start: \Carbon\CarbonImmutable, end: \Carbon\CarbonImmutable}  $range
     */
    private function csv(ExpenseAggregator $aggregator, array $filters, array $range, string $rangeLabel): StreamedResponse
    {
        $filename = 'reporte-gastos-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($aggregator, $filters, $range): void {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Folio', 'Solicitante', 'Rol', 'Región', 'Estado',
                'Concepto', 'Estado', 'Entrega',
                'Solicitado', 'Aprobado', 'Pagado', 'Fecha',
            ]);

            $aggregator
                ->baseQuery($filters, $range)
                ->with(['user.region', 'user.state', 'user.role', 'expenseConcept', 'payments'])
                ->latest()
                ->chunkById(500, function ($chunk) use ($handle): void {
                    foreach ($chunk as $r) {
                        /** @var ExpenseRequest $r */
                        fputcsv($handle, [
                            $r->folio,
                            $r->user->name,
                            $r->user->role?->name,
                            $r->user->region?->name,
                            $r->user->state?->name,
                            $r->conceptLabel(),
                            $r->status->label(),
                            $r->delivery_method->label(),
                            number_format($r->requested_amount_cents / 100, 2, '.', ''),
                            $r->approved_amount_cents !== null
                                ? number_format($r->approved_amount_cents / 100, 2, '.', '')
                                : '',
                            number_format($r->payments->sum('amount_cents') / 100, 2, '.', ''),
                            $r->created_at?->format('Y-m-d'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resolveFilters(Request $request): array
    {
        $keys = [
            'search', 'status', 'region_id', 'state_id', 'user_id',
            'expense_concept_id', 'delivery_method', 'role_id',
            'date_from', 'date_to',
        ];

        $out = [];
        foreach ($keys as $k) {
            $out[$k] = (string) $request->query($k, '');
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<string>
     */
    private function labelFilters(array $filters, string $rangeLabel): array
    {
        $out = ['Periodo: '.$rangeLabel];
        foreach ($filters as $k => $v) {
            if ($v === '') {
                continue;
            }
            $out[] = ucfirst(str_replace('_', ' ', $k)).': '.$v;
        }

        return $out;
    }
}
