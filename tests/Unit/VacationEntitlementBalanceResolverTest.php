<?php

namespace Tests\Unit;

use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationEntitlement;
use App\Models\VacationRequest;
use App\Models\VacationRule;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacationEntitlementBalanceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_years_below_one_day_before_anniversary(): void
    {
        $resolver = new VacationEntitlementBalanceResolver;
        $hire = CarbonImmutable::parse('2020-03-15');
        $asOf = CarbonImmutable::parse('2021-03-14');

        $this->assertLessThan(1.0, $resolver->serviceYears($hire, $asOf));
    }

    public function test_service_years_at_least_one_on_anniversary(): void
    {
        $resolver = new VacationEntitlementBalanceResolver;
        $hire = CarbonImmutable::parse('2020-03-15');
        $asOf = CarbonImmutable::parse('2021-03-15');

        $this->assertGreaterThanOrEqual(1.0, $resolver->serviceYears($hire, $asOf));
    }

    public function test_resolve_rule_picks_first_matching_sort_order(): void
    {
        VacationRule::factory()->create([
            'code' => 'SENIOR',
            'min_years_service' => 5,
            'max_years_service' => null,
            'days_granted_per_year' => 20,
            'sort_order' => 1,
        ]);
        VacationRule::factory()->create([
            'code' => 'STANDARD',
            'min_years_service' => 1,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'sort_order' => 2,
        ]);

        $resolver = new VacationEntitlementBalanceResolver;

        $this->assertSame('STANDARD', $resolver->resolveRule(2.0)?->code);
        $this->assertSame('SENIOR', $resolver->resolveRule(6.0)?->code);
    }

    public function test_consumed_days_sums_only_pipeline_statuses(): void
    {
        $user = User::factory()->withHireDate(CarbonImmutable::parse('2019-01-01'))->create();

        foreach (
            [
                VacationRequestStatus::Draft,
                VacationRequestStatus::Rejected,
                VacationRequestStatus::Cancelled,
            ] as $status
        ) {
            VacationRequest::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
                'starts_on' => '2026-03-02',
                'ends_on' => '2026-03-06',
                'business_days_count' => 5,
            ]);
        }

        VacationRequest::factory()->create([
            'user_id' => $user->id,
            'status' => VacationRequestStatus::Submitted,
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-03',
            'business_days_count' => 3,
        ]);

        $resolver = new VacationEntitlementBalanceResolver;

        $this->assertSame(3, $resolver->consumedDaysForUserInYear($user, 2026));
    }

    public function test_creates_entitlement_when_missing(): void
    {
        VacationRule::factory()->create([
            'code' => 'LFT',
            'min_years_service' => 1,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'sort_order' => 1,
        ]);

        $user = User::factory()->withHireDate(CarbonImmutable::parse('2018-06-01'))->create();
        $resolver = new VacationEntitlementBalanceResolver;

        $payload = $resolver->resolveForUser($user, CarbonImmutable::parse('2026-06-15'), 2026);

        $this->assertSame(12, $payload['days_allocated']);
        $this->assertDatabaseHas('vacation_entitlements', [
            'user_id' => $user->id,
            'calendar_year' => 2026,
            'days_allocated' => 12,
        ]);
    }

    public function test_respects_existing_entitlement_allocation(): void
    {
        $rule = VacationRule::factory()->create([
            'code' => 'LFT',
            'min_years_service' => 1,
            'days_granted_per_year' => 12,
            'sort_order' => 1,
        ]);

        $user = User::factory()->withHireDate(CarbonImmutable::parse('2018-06-01'))->create();

        VacationEntitlement::factory()->create([
            'user_id' => $user->id,
            'calendar_year' => 2026,
            'days_allocated' => 20,
            'vacation_rule_id' => $rule->id,
        ]);

        $resolver = new VacationEntitlementBalanceResolver;
        $payload = $resolver->resolveForUser($user, CarbonImmutable::parse('2026-06-15'), 2026);

        $this->assertSame(20, $payload['days_allocated']);
    }

    public function test_pending_first_year_when_no_tramo_matches(): void
    {
        VacationRule::factory()->create([
            'code' => 'ONE_PLUS',
            'min_years_service' => 1,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'sort_order' => 1,
        ]);

        $user = User::factory()->withHireDate(CarbonImmutable::parse('2025-06-01'))->create();
        $resolver = new VacationEntitlementBalanceResolver;

        $payload = $resolver->resolveForUser($user, CarbonImmutable::parse('2025-12-01'), 2025);

        $this->assertTrue($payload['pending_first_year']);
        $this->assertNull($payload['rule']);
        $this->assertSame('2026-06-01', $payload['first_anniversary_on']);
        $this->assertNotNull($payload['days_until_anniversary']);
    }

    public function test_missing_hire_date_payload(): void
    {
        $user = User::factory()->create(['hire_date' => null]);
        $resolver = new VacationEntitlementBalanceResolver;

        $payload = $resolver->resolveForUser($user, CarbonImmutable::parse('2026-01-01'), 2026);

        $this->assertFalse($payload['has_hire_date']);
        $this->assertSame(0, $payload['days_remaining']);
    }
}
