<?php

namespace App\Services\VacationRequests;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Cuenta días hábiles (lunes a viernes) entre dos fechas, inclusive.
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

        $count = 0;
        while ($current->lte($last)) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current = $current->addDay();
        }

        return $count;
    }
}
