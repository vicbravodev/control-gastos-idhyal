<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\CombineWithNext;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequestApproval;
use App\Models\VacationRequestApproval;
use Illuminate\Support\Collection;

final class ApprovalStepGrouper
{
    /**
     * Group ordered policy steps by combine_with_next: consecutive steps linked with `or` share a group;
     * `and` closes the current group and starts the next.
     *
     * @param  Collection<int, ApprovalPolicyStep>  $orderedSteps
     * @return list<list<int>> Lists of step_order values per group
     */
    public static function stepOrderGroups(Collection $orderedSteps): array
    {
        if ($orderedSteps->isEmpty()) {
            return [];
        }

        /** @var list<list<int>> $groups */
        $groups = [];
        /** @var list<int> $current */
        $current = [$orderedSteps->first()->step_order];

        $count = $orderedSteps->count();
        for ($i = 0; $i < $count - 1; $i++) {
            /** @var ApprovalPolicyStep $step */
            $step = $orderedSteps->values()[$i];
            /** @var ApprovalPolicyStep $next */
            $next = $orderedSteps->values()[$i + 1];

            if ($step->combine_with_next === CombineWithNext::Or) {
                $current[] = $next->step_order;
            } else {
                $groups[] = $current;
                $current = [$next->step_order];
            }
        }

        $groups[] = $current;

        return $groups;
    }

    /**
     * @param  Collection<int, ExpenseRequestApproval|VacationRequestApproval>  $approvals
     * @param  list<list<int>>  $stepOrderGroups
     */
    public static function firstIncompleteGroupIndex(Collection $approvals, array $stepOrderGroups): ?int
    {
        foreach ($stepOrderGroups as $index => $stepOrders) {
            if (! self::isGroupSatisfied($approvals, $stepOrders)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, ExpenseRequestApproval|VacationRequestApproval>  $approvals
     * @param  list<int>  $stepOrdersInGroup
     */
    public static function isGroupSatisfied(Collection $approvals, array $stepOrdersInGroup): bool
    {
        return $approvals
            ->filter(fn ($a) => in_array($a->step_order, $stepOrdersInGroup, true))
            ->contains(fn ($a) => $a->status === ApprovalInstanceStatus::Approved);
    }
}
