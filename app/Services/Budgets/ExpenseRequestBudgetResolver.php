<?php

namespace App\Services\Budgets;

use App\Models\Budget;
use App\Models\ExpenseRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves the single budget that applies to an expense request when several overlap:
 * higher {@see Budget::$priority} wins; on tie, narrower scope wins (user > role > state > region).
 */
final class ExpenseRequestBudgetResolver
{
    /**
     * Intrinsic specificity when {@see Budget::$priority} is equal (data-dictionary-stage2 §1.4).
     */
    private const array SCOPE_RANK = [
        'user' => 4,
        'role' => 3,
        'state' => 2,
        'region' => 1,
    ];

    public function resolve(ExpenseRequest $expenseRequest): ?Budget
    {
        $expenseRequest->loadMissing('user');

        $user = $expenseRequest->user;
        if ($user === null) {
            return null;
        }

        [$windowStart, $windowEnd] = $this->expenseWindow($expenseRequest);

        $query = Budget::query()
            ->where(function (Builder $outer) use ($user): void {
                $outer->where(function (Builder $q) use ($user): void {
                    $q->where('budgetable_type', 'user')
                        ->where('budgetable_id', $user->id);
                });

                if ($user->role_id !== null) {
                    $outer->orWhere(function (Builder $q) use ($user): void {
                        $q->where('budgetable_type', 'role')
                            ->where('budgetable_id', $user->role_id);
                    });
                }

                if ($user->state_id !== null) {
                    $outer->orWhere(function (Builder $q) use ($user): void {
                        $q->where('budgetable_type', 'state')
                            ->where('budgetable_id', $user->state_id);
                    });
                }

                if ($user->region_id !== null) {
                    $outer->orWhere(function (Builder $q) use ($user): void {
                        $q->where('budgetable_type', 'region')
                            ->where('budgetable_id', $user->region_id);
                    });
                }
            })
            ->whereDate('period_starts_on', '<=', $windowEnd)
            ->whereDate('period_ends_on', '>=', $windowStart);

        /** @var Collection<int, Budget> $candidates */
        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->sort(function (Budget $a, Budget $b): int {
            $pa = $a->priority ?? 0;
            $pb = $b->priority ?? 0;
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }

            $ra = self::SCOPE_RANK[$a->budgetable_type] ?? 0;
            $rb = self::SCOPE_RANK[$b->budgetable_type] ?? 0;
            if ($ra !== $rb) {
                return $rb <=> $ra;
            }

            return $a->id <=> $b->id;
        })->first();
    }

    /**
     * @return array{0: string, 1: string} ISO dates (Y-m-d) inclusive window for overlap checks.
     */
    private function expenseWindow(ExpenseRequest $expenseRequest): array
    {
        $day = CarbonImmutable::parse($expenseRequest->created_at)->startOfDay()->toDateString();

        return [$day, $day];
    }
}
