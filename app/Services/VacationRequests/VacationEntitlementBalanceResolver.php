<?php

namespace App\Services\VacationRequests;

use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationEntitlement;
use App\Models\VacationRequest;
use App\Models\VacationRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Resolves vacation balance from hire date, {@see VacationRule} tiers, optional
 * materialized {@see VacationEntitlement}, and consuming {@see VacationRequest} rows.
 *
 * Service years: fractional years from hire date to the service-as-of date via
 * {@see CarbonImmutable::floatDiffInYears} (anniversary-continuous, comparable to rule tramos).
 */
class VacationEntitlementBalanceResolver
{
    /**
     * @var list<VacationRequestStatus>
     */
    public const CONSUMING_STATUSES = [
        VacationRequestStatus::Submitted,
        VacationRequestStatus::ApprovalInProgress,
        VacationRequestStatus::Approved,
        VacationRequestStatus::Completed,
    ];

    /**
     * Fractional years of service from hire (inclusive) through as-of (inclusive comparison by day).
     */
    public function serviceYears(CarbonImmutable $hireDate, CarbonImmutable $asOf): float
    {
        $hire = $hireDate->startOfDay();
        $cut = $asOf->startOfDay();
        if ($cut < $hire) {
            return 0.0;
        }

        return max(0.0, (float) $hire->floatDiffInYears($cut));
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForUser(
        User $user,
        ?CarbonImmutable $serviceAsOf = null,
        ?int $entitlementCalendarYear = null,
    ): array {
        $serviceAsOf = ($serviceAsOf ?? CarbonImmutable::now())->startOfDay();
        $calendarYear = $entitlementCalendarYear ?? (int) $serviceAsOf->year;

        if ($user->hire_date === null) {
            return $this->missingHirePayload($calendarYear);
        }

        $hire = CarbonImmutable::parse($user->hire_date)->startOfDay();
        $years = $this->serviceYears($hire, $serviceAsOf);
        $rule = $this->resolveRule($years);

        if ($rule === null) {
            return $this->pendingFirstYearPayload($hire, $serviceAsOf, $calendarYear, $years, $user);
        }

        $entitlement = $this->ensureEntitlement($user, $calendarYear, $rule);
        $daysAllocated = $entitlement->days_allocated;
        $consumed = $this->consumedDaysForUserInYear($user, $calendarYear);
        $remaining = max(0, $daysAllocated - $consumed);

        return [
            'has_hire_date' => true,
            'service_years' => round($years, 2),
            'calendar_year' => $calendarYear,
            'rule' => [
                'id' => $rule->id,
                'name' => $rule->name,
                'code' => $rule->code,
                'max_days_per_request' => $rule->max_days_per_request,
            ],
            'days_allocated' => $daysAllocated,
            'days_consumed' => $consumed,
            'days_remaining' => $remaining,
            'pending_first_year' => false,
            'first_anniversary_on' => null,
            'days_until_anniversary' => null,
        ];
    }

    public function resolveRule(float $serviceYears): ?VacationRule
    {
        $rules = VacationRule::query()->orderBy('sort_order')->orderBy('id')->get();

        foreach ($rules as $rule) {
            $min = (float) $rule->min_years_service;
            $max = $rule->max_years_service !== null ? (float) $rule->max_years_service : null;

            if ($serviceYears + 1e-9 < $min) {
                continue;
            }
            if ($max !== null && $serviceYears - 1e-9 > $max) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    public function consumedDaysForUserInYear(User $user, int $calendarYear): int
    {
        $statusValues = array_map(
            static fn (VacationRequestStatus $s): string => $s->value,
            self::CONSUMING_STATUSES,
        );

        return (int) VacationRequest::query()
            ->where('user_id', $user->id)
            ->whereYear('starts_on', $calendarYear)
            ->whereIn('status', $statusValues)
            ->sum('business_days_count');
    }

    /**
     * @return array<string, mixed>
     */
    private function missingHirePayload(int $calendarYear): array
    {
        return [
            'has_hire_date' => false,
            'service_years' => null,
            'calendar_year' => $calendarYear,
            'rule' => null,
            'days_allocated' => 0,
            'days_consumed' => 0,
            'days_remaining' => 0,
            'pending_first_year' => false,
            'first_anniversary_on' => null,
            'days_until_anniversary' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingFirstYearPayload(
        CarbonImmutable $hire,
        CarbonImmutable $serviceAsOf,
        int $calendarYear,
        float $years,
        User $user,
    ): array {
        $firstAnniversary = $hire->addYear();
        $daysUntil = null;
        if ($serviceAsOf < $firstAnniversary) {
            $raw = $serviceAsOf->diffInDays($firstAnniversary, false);
            $daysUntil = max(0, $raw);
        }

        return [
            'has_hire_date' => true,
            'service_years' => round($years, 2),
            'calendar_year' => $calendarYear,
            'rule' => null,
            'days_allocated' => 0,
            'days_consumed' => $this->consumedDaysForUserInYear($user, $calendarYear),
            'days_remaining' => 0,
            'pending_first_year' => true,
            'first_anniversary_on' => $firstAnniversary->toDateString(),
            'days_until_anniversary' => $daysUntil,
        ];
    }

    private function ensureEntitlement(User $user, int $calendarYear, VacationRule $rule): VacationEntitlement
    {
        return DB::transaction(function () use ($user, $calendarYear, $rule): VacationEntitlement {
            return VacationEntitlement::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'calendar_year' => $calendarYear,
                ],
                [
                    'days_allocated' => $rule->days_granted_per_year,
                    'days_used' => 0,
                    'vacation_rule_id' => $rule->id,
                ],
            );
        });
    }
}
