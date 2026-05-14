<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportScheduleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ReportSchedule::class);

        $data = $request->validate([
            'template_id' => ['required', 'integer', Rule::exists('report_templates', 'id')],
            'cadence' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'time_of_day' => ['required', 'date_format:H:i'],
            'format' => ['required', Rule::in(['pdf', 'csv'])],
            'recipients' => ['required', 'array', 'min:1', 'max:20'],
            'recipients.*' => ['email'],
            'active' => ['boolean'],
        ]);

        // Ensure the chosen template is accessible to the user.
        $template = ReportTemplate::query()->findOrFail($data['template_id']);
        $this->authorize('view', $template);

        $schedule = ReportSchedule::query()->create([
            'owner_user_id' => $request->user()->id,
            'template_id' => $data['template_id'],
            'cadence' => $data['cadence'],
            'day_of_week' => $data['day_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'time_of_day' => $data['time_of_day'],
            'format' => $data['format'],
            'recipients' => $data['recipients'],
            'active' => (bool) ($data['active'] ?? true),
            'next_run_at' => $this->computeNextRun($data),
        ]);

        return redirect()
            ->back()
            ->with('status', __('Reporte programado.'));
    }

    public function update(Request $request, ReportSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $data = $request->validate([
            'cadence' => ['sometimes', 'required', Rule::in(['daily', 'weekly', 'monthly'])],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'time_of_day' => ['sometimes', 'required', 'date_format:H:i'],
            'format' => ['sometimes', 'required', Rule::in(['pdf', 'csv'])],
            'recipients' => ['sometimes', 'required', 'array', 'min:1', 'max:20'],
            'recipients.*' => ['email'],
            'active' => ['boolean'],
        ]);

        $schedule->fill($data);

        if (isset($data['cadence']) || isset($data['day_of_week']) || isset($data['day_of_month']) || isset($data['time_of_day'])) {
            $schedule->next_run_at = $this->computeNextRun(array_merge($schedule->toArray(), $data));
        }

        $schedule->save();

        return redirect()
            ->back()
            ->with('status', __('Programación actualizada.'));
    }

    public function destroy(Request $request, ReportSchedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $schedule->delete();

        return redirect()
            ->back()
            ->with('status', __('Programación eliminada.'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function computeNextRun(array $data): Carbon
    {
        $tz = 'America/Mexico_City';
        $time = $data['time_of_day'] ?? '07:00';
        [$h, $m] = array_pad(explode(':', $time), 2, 0);

        $now = Carbon::now($tz);
        $cadence = $data['cadence'] ?? 'daily';

        $candidate = $now->copy()->setTime((int) $h, (int) $m, 0);

        if ($cadence === 'daily') {
            if ($candidate <= $now) {
                $candidate = $candidate->copy()->addDay();
            }

            return $candidate->setTimezone('UTC');
        }

        if ($cadence === 'weekly') {
            $dow = (int) ($data['day_of_week'] ?? 1); // 0=Sunday … 6=Saturday
            while ($candidate->dayOfWeek !== $dow || $candidate <= $now) {
                $candidate = $candidate->copy()->addDay();
            }

            return $candidate->setTimezone('UTC');
        }

        // monthly
        $dom = (int) ($data['day_of_month'] ?? 1);
        $candidate = $candidate->copy()->day(min($dom, $candidate->daysInMonth));
        if ($candidate <= $now) {
            $candidate = $candidate->copy()->addMonthNoOverflow()
                ->day(min($dom, $candidate->copy()->addMonthNoOverflow()->daysInMonth));
        }

        return $candidate->setTimezone('UTC');
    }
}
