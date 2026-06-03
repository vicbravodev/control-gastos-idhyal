<?php

namespace Tests\Unit;

use App\Models\Holiday;
use App\Services\VacationRequests\VacationBusinessDayCounter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VacationBusinessDayCounterTest extends TestCase
{
    use RefreshDatabase;

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

    #[Test]
    public function it_excludes_registered_holidays(): void
    {
        // Sept 16 (Independencia) is a Wednesday in 2026 — falls inside the window 14-17 Sept.
        Holiday::query()->create([
            'date' => '2026-09-16',
            'name' => 'Día de la Independencia',
        ]);

        $counter = new VacationBusinessDayCounter;
        $start = CarbonImmutable::parse('2026-09-14');
        $end = CarbonImmutable::parse('2026-09-17');

        // Mon 14, Tue 15, Wed 16 (holiday), Thu 17 → 3 days (not 4)
        $this->assertSame(3, $counter->countInclusive($start, $end));
    }

    #[Test]
    public function it_ignores_holidays_outside_the_window(): void
    {
        Holiday::query()->create([
            'date' => '2026-01-01',
            'name' => 'Año nuevo',
        ]);

        $counter = new VacationBusinessDayCounter;
        $start = CarbonImmutable::parse('2026-03-23');
        $end = CarbonImmutable::parse('2026-03-27');

        $this->assertSame(5, $counter->countInclusive($start, $end));
    }

    #[Test]
    public function holiday_falling_on_weekend_does_not_double_subtract(): void
    {
        Holiday::query()->create([
            'date' => '2026-03-21', // Saturday
            'name' => 'Festivo sábado',
        ]);

        $counter = new VacationBusinessDayCounter;
        $start = CarbonImmutable::parse('2026-03-20');
        $end = CarbonImmutable::parse('2026-03-23');

        // Fri 20, Sat 21 (holiday + weekend), Sun 22, Mon 23 → 2 weekdays
        $this->assertSame(2, $counter->countInclusive($start, $end));
    }
}
