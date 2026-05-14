<?php

namespace App\Console\Commands\Reports;

use App\Jobs\Reports\RunScheduledReport;
use App\Models\ReportSchedule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:dispatch-due-schedules')]
#[Description('Dispatch scheduled report jobs whose next_run_at is due.')]
class DispatchDueSchedulesCommand extends Command
{
    public function handle(): int
    {
        $count = 0;

        ReportSchedule::query()
            ->where('active', true)
            ->where('next_run_at', '<=', now())
            ->chunkById(100, function ($chunk) use (&$count): void {
                foreach ($chunk as $schedule) {
                    RunScheduledReport::dispatch($schedule->id);
                    $count++;
                }
            });

        $this->info(__('Dispatched :count scheduled report(s).', ['count' => (string) $count]));

        return self::SUCCESS;
    }
}
