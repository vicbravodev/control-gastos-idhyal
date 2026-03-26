<?php

namespace Tests\Unit;

use App\Services\VacationRequests\VacationBusinessDayCounter;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VacationBusinessDayCounterTest extends TestCase
{
    #[Test]
    public function it_counts_weekdays_inclusive(): void
    {
        $counter = new VacationBusinessDayCounter;
        $start = CarbonImmutable::parse('2026-03-23');
        $end = CarbonImmutable::parse('2026-03-27');

        $this->assertSame(5, $counter->countInclusive($start, $end));
    }

    #[Test]
    public function it_excludes_weekends(): void
    {
        $counter = new VacationBusinessDayCounter;
        $start = CarbonImmutable::parse('2026-03-21');
        $end = CarbonImmutable::parse('2026-03-22');

        $this->assertSame(0, $counter->countInclusive($start, $end));
    }
}
