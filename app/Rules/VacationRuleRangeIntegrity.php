<?php

namespace App\Rules;

use App\Models\VacationRule;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a vacation rule's [min_years_service, max_years_service]
 * range does not overlap with any existing active rule (excluding itself
 * when updating).
 */
class VacationRuleRangeIntegrity implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(protected ?int $excludeId = null) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $min = (float) ($this->data['min_years_service'] ?? 0);
        $max = $this->data['max_years_service'] ?? null;
        $max = ($max === null || $max === '') ? null : (float) $max;

        if ($max !== null && $max < $min) {
            return; // Caller's separate min/max comparison surfaces this.
        }

        $query = VacationRule::query();
        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        $others = $query->get(['id', 'code', 'min_years_service', 'max_years_service']);

        foreach ($others as $other) {
            $otherMin = (float) $other->min_years_service;
            $otherMax = $other->max_years_service !== null ? (float) $other->max_years_service : null;

            if ($this->rangesOverlap($min, $max, $otherMin, $otherMax)) {
                $fail(sprintf(
                    'El rango se solapa con la regla "%s" (%s a %s años).',
                    $other->code,
                    $this->formatYears($otherMin),
                    $otherMax === null ? '∞' : $this->formatYears($otherMax),
                ));

                return;
            }
        }
    }

    private function rangesOverlap(float $aMin, ?float $aMax, float $bMin, ?float $bMax): bool
    {
        $aEnd = $aMax ?? PHP_FLOAT_MAX;
        $bEnd = $bMax ?? PHP_FLOAT_MAX;

        // Tolerance for fractional comparisons.
        $eps = 1e-6;

        return ($aMin - $eps) <= $bEnd && ($aEnd + $eps) >= $bMin;
    }

    private function formatYears(float $years): string
    {
        return rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.');
    }
}
