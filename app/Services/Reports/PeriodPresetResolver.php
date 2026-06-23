<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class PeriodPresetResolver
{
    public const PRESETS = [
        'mtd' => 'Mes actual',
        'lm' => 'Mes anterior',
        'qtd' => 'Trimestre actual',
        'ytd' => 'Año a la fecha',
        'l30' => 'Últimos 30 días',
        'l90' => 'Últimos 90 días',
        'l12m' => 'Últimos 12 meses',
        'custom' => 'Rango personalizado',
    ];

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     granularity: 'day'|'week'|'month',
     *     range_label: string
     * }
     */
    public function resolve(?string $preset, ?string $customFrom = null, ?string $customTo = null): array
    {
        $now = CarbonImmutable::now('America/Mexico_City');
        $preset = $preset && array_key_exists($preset, self::PRESETS) ? $preset : 'ytd';

        [$start, $end, $granularity] = match ($preset) {
            'mtd' => [$now->startOfMonth(), $now->endOfDay(), 'day'],
            'lm' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'day',
            ],
            'qtd' => [$now->startOfQuarter(), $now->endOfDay(), 'week'],
            'ytd' => [$now->startOfYear(), $now->endOfDay(), 'month'],
            'l30' => [$now->copy()->subDays(29)->startOfDay(), $now->endOfDay(), 'day'],
            'l90' => [$now->copy()->subDays(89)->startOfDay(), $now->endOfDay(), 'week'],
            'l12m' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->endOfDay(), 'month'],
            'custom' => [
                $customFrom ? CarbonImmutable::parse($customFrom, 'America/Mexico_City')->startOfDay() : $now->startOfMonth(),
                $customTo ? CarbonImmutable::parse($customTo, 'America/Mexico_City')->endOfDay() : $now->endOfDay(),
                'day',
            ],
        };

        $spanSeconds = (int) abs($end->diffInSeconds($start));
        $prevEnd = $start->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subSeconds($spanSeconds);

        return [
            'id' => $preset,
            'label' => self::PRESETS[$preset],
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd,
            'granularity' => $granularity,
            'range_label' => $this->formatRangeLabel($start, $end),
        ];
    }

    private function formatRangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $monthsShort = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        $sameYear = $start->year === $end->year;
        $sameMonth = $sameYear && $start->month === $end->month;

        if ($sameMonth) {
            return sprintf(
                '%d – %d %s %d',
                $start->day,
                $end->day,
                $monthsShort[$start->month - 1],
                $end->year,
            );
        }

        if ($sameYear) {
            return sprintf(
                '%d %s – %d %s %d',
                $start->day,
                $monthsShort[$start->month - 1],
                $end->day,
                $monthsShort[$end->month - 1],
                $end->year,
            );
        }

        return sprintf(
            '%d %s %d – %d %s %d',
            $start->day,
            $monthsShort[$start->month - 1],
            $start->year,
            $end->day,
            $monthsShort[$end->month - 1],
            $end->year,
        );
    }

    /**
     * @return list<array{id: string, label: string, range_label: string}>
     */
    public function listPresets(): array
    {
        $out = [];

        foreach (array_keys(self::PRESETS) as $id) {
            $resolved = $this->resolve($id);
            $out[] = [
                'id' => $id,
                'label' => $resolved['label'],
                'range_label' => $id === 'custom' ? '' : $resolved['range_label'],
            ];
        }

        return $out;
    }
}
