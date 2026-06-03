<?php

namespace App\Services\VacationRequests;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Cuenta días hábiles (lunes a viernes) entre dos fechas, inclusive,
 * descontando festivos registrados en la tabla `holidays`.
 */
final class VacationBusinessDayCounter
{
    public function countInclusive(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $current = CarbonImmutable::parse($start)->startOfDay();
        $last = CarbonImmutable::parse($end)->startOfDay();

        if ($current->gt($last)) {
            return 0;
        }

        $holidays = Holiday::query()
            ->whereBetween('date', [$current->toDateString(), $last->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
            ->all();
        $holidaySet = array_flip($holidays);

        $count = 0;
        while ($current->lte($last)) {
            if ($current->isWeekday() && ! isset($holidaySet[$current->toDateString()])) {
                $count++;
            }
            $current = $current->addDay();
        }

        return $count;
    }
}
