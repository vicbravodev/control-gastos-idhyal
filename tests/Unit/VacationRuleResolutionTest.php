<?php

namespace Tests\Unit;

use App\Models\VacationRule;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VacationRuleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Order from highest min to lowest so the first match (sort_order ASC)
        // is the user's correct tier. No max needed: each tier extends until
        // the next-higher tier kicks in.
        $tiers = [
            ['code' => 'LFT_2023_T30_PLUS', 'min' => 30.0, 'days' => 30, 'order' => 1],
            ['code' => 'LFT_2023_T25_29', 'min' => 25.0, 'days' => 28, 'order' => 2],
            ['code' => 'LFT_2023_T20_24', 'min' => 20.0, 'days' => 26, 'order' => 3],
            ['code' => 'LFT_2023_T15_19', 'min' => 15.0, 'days' => 24, 'order' => 4],
            ['code' => 'LFT_2023_T10_14', 'min' => 10.0, 'days' => 22, 'order' => 5],
            ['code' => 'LFT_2023_T5_9', 'min' => 5.0, 'days' => 20, 'order' => 6],
            ['code' => 'LFT_2023_T4', 'min' => 4.0, 'days' => 18, 'order' => 7],
            ['code' => 'LFT_2023_T3', 'min' => 3.0, 'days' => 16, 'order' => 8],
            ['code' => 'LFT_2023_T2', 'min' => 2.0, 'days' => 14, 'order' => 9],
            ['code' => 'LFT_2023_T1', 'min' => 1.0, 'days' => 12, 'order' => 10],
        ];

        foreach ($tiers as $tier) {
            VacationRule::query()->create([
                'code' => $tier['code'],
                'name' => 'Tier '.$tier['code'],
                'min_years_service' => $tier['min'],
                'max_years_service' => null,
                'days_granted_per_year' => $tier['days'],
                'max_days_per_request' => $tier['days'],
                'max_days_per_month' => $tier['days'],
                'max_days_per_quarter' => $tier['days'],
                'max_days_per_year' => $tier['days'],
                'blackout_dates' => [],
                'sort_order' => $tier['order'],
            ]);
        }
    }

    /**
     * @return array<string, array{0: float, 1: int|null}>
     */
    public static function tierProvider(): array
    {
        return [
            'half year (pre-first-year)' => [0.5, null],
            'exactly 1 year (anniversary)' => [1.0, 12],
            '1.5 years' => [1.5, 12],
            '1.999 years' => [1.999, 12],
            'exactly 2 years' => [2.0, 14],
            '3 years' => [3.0, 16],
            '4.999 years' => [4.999, 18],
            'exactly 5 years' => [5.0, 20],
            '9 years (the bug case)' => [9.0, 20],
            '9.999 years' => [9.999, 20],
            'exactly 10 years' => [10.0, 22],
            '15 years' => [15.0, 24],
            '20 years' => [20.0, 26],
            '30 years' => [30.0, 30],
            '40 years' => [40.0, 30],
        ];
    }

    #[DataProvider('tierProvider')]
    public function test_resolve_rule_returns_expected_days(float $serviceYears, ?int $expectedDays): void
    {
        $resolver = app(VacationEntitlementBalanceResolver::class);
        $rule = $resolver->resolveRule($serviceYears);

        if ($expectedDays === null) {
            $this->assertNull($rule, "Expected no rule for $serviceYears years.");

            return;
        }

        $this->assertNotNull($rule, "Expected a rule for $serviceYears years.");
        $this->assertSame(
            $expectedDays,
            $rule->days_granted_per_year,
            "Expected $expectedDays days for $serviceYears years, got {$rule->days_granted_per_year} (rule {$rule->code})."
        );
    }

    public function test_service_years_for_user_with_9_years_returns_20_days(): void
    {
        $resolver = app(VacationEntitlementBalanceResolver::class);

        $hire = CarbonImmutable::create(2017, 5, 8);
        $asOf = CarbonImmutable::create(2026, 5, 8);

        $years = $resolver->serviceYears($hire, $asOf);
        $this->assertEqualsWithDelta(9.0, $years, 0.01);

        $rule = $resolver->resolveRule($years);
        $this->assertNotNull($rule);
        $this->assertSame(20, $rule->days_granted_per_year, 'A user with 9 years must get 20 vacation days, not 12.');
    }
}
