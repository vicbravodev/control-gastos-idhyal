<?php

namespace App\Jobs\Reports;

use App\Mail\ScheduledReportDelivery;
use App\Models\ReportSchedule;
use App\Services\Reports\ExpenseAggregator;
use App\Services\Reports\PeriodPresetResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RunScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $scheduleId) {}

    public function handle(
        PeriodPresetResolver $resolver,
        ExpenseAggregator $aggregator,
    ): void {
        $schedule = ReportSchedule::query()
            ->with('template', 'owner')
            ->findOrFail($this->scheduleId);

        if (! $schedule->active) {
            return;
        }

        $template = $schedule->template;
        $filters = is_array($template?->filters) ? $template->filters : [];
        $period = $filters['period'] ?? 'mtd';

        $resolved = $resolver->resolve($period);
        $range = ['start' => $resolved['start'], 'end' => $resolved['end']];

        $stem = 'reporte-'.($template?->slug ?? 'custom').'-'.now()->format('Y-m-d-His');
        $filename = $stem.'.'.$schedule->format;
        $relPath = "scheduled-reports/{$filename}";

        Storage::disk('local')->makeDirectory('scheduled-reports');

        if ($schedule->format === 'pdf') {
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
                'activeFilters' => ['Periodo: '.$resolved['range_label']],
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape');

            Storage::disk('local')->put($relPath, $pdf->output());
        } else {
            // csv
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Folio', 'Solicitante', 'Concepto', 'Estado', 'Solicitado', 'Aprobado', 'Pagado', 'Fecha']);
            $aggregator
                ->baseQuery($filters, $range)
                ->with(['user', 'expenseConcept', 'payments'])
                ->latest()
                ->chunkById(500, function ($chunk) use ($handle): void {
                    foreach ($chunk as $r) {
                        fputcsv($handle, [
                            $r->folio,
                            $r->user->name,
                            $r->conceptLabel(),
                            $r->status->label(),
                            number_format($r->requested_amount_cents / 100, 2, '.', ''),
                            $r->approved_amount_cents !== null
                                ? number_format($r->approved_amount_cents / 100, 2, '.', '')
                                : '',
                            number_format($r->payments->sum('amount_cents') / 100, 2, '.', ''),
                            $r->created_at?->format('Y-m-d'),
                        ]);
                    }
                });
            rewind($handle);
            Storage::disk('local')->put($relPath, stream_get_contents($handle));
            fclose($handle);
        }

        $absolutePath = Storage::disk('local')->path($relPath);

        Mail::to($schedule->recipients)
            ->send(new ScheduledReportDelivery($schedule, $absolutePath, $filename));

        $schedule->update([
            'last_run_at' => now(),
            'next_run_at' => $this->computeNextRun($schedule),
        ]);

        // Best-effort cleanup
        Storage::disk('local')->delete($relPath);
    }

    private function computeNextRun(ReportSchedule $schedule): \Carbon\Carbon
    {
        $tz = $schedule->tz ?? 'America/Mexico_City';
        [$h, $m] = array_pad(explode(':', $schedule->time_of_day), 2, 0);

        $candidate = \Carbon\Carbon::now($tz)->setTime((int) $h, (int) $m, 0);

        if ($schedule->cadence === 'daily') {
            return $candidate->copy()->addDay()->setTimezone('UTC');
        }

        if ($schedule->cadence === 'weekly') {
            $candidate = $candidate->copy()->addDay();
            while ($candidate->dayOfWeek !== (int) ($schedule->day_of_week ?? 1)) {
                $candidate = $candidate->copy()->addDay();
            }

            return $candidate->setTimezone('UTC');
        }

        $candidate = $candidate->copy()->addMonthNoOverflow();
        $dom = (int) ($schedule->day_of_month ?? 1);

        return $candidate
            ->day(min($dom, $candidate->daysInMonth))
            ->setTimezone('UTC');
    }
}
