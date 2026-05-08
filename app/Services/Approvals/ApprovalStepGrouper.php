<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalGroupCombinator;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequestApproval;
use App\Models\VacationRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use Illuminate\Support\Collection;

final class ApprovalStepGrouper
{
    /**
     * Group ordered policy steps by step_mode:
     *   - sequential closes the current group and starts the next
     *   - any_of / all_of keep the next step in the current group
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

            if ($step->step_mode === ApprovalStepMode::Sequential) {
                $groups[] = $current;
                $current = [$next->step_order];
            } else {
                $current[] = $next->step_order;
            }
        }

        $groups[] = $current;

        return $groups;
    }

    /**
     * Determine the combinator (AnyOf / AllOf) for a group of step orders.
     * A group cannot mix any_of and all_of modes.
     *
     * @param  Collection<int, ApprovalPolicyStep>  $orderedSteps
     * @param  list<int>  $stepOrdersInGroup
     *
     * @throws InvalidApprovalStateException when a group mixes any_of and all_of.
     */
    public static function groupCombinator(Collection $orderedSteps, array $stepOrdersInGroup): ApprovalGroupCombinator
    {
        if (count($stepOrdersInGroup) <= 1) {
            return ApprovalGroupCombinator::AnyOf;
        }

        $modes = $orderedSteps
            ->filter(fn (ApprovalPolicyStep $s): bool => in_array($s->step_order, $stepOrdersInGroup, true))
            ->sortBy('step_order')
            ->values();

        // Inspect every step except the last one in the group: those are the
        // ones whose step_mode dictates how they combine with the next.
        $combinators = [];
        for ($i = 0; $i < $modes->count() - 1; $i++) {
            /** @var ApprovalPolicyStep $step */
            $step = $modes[$i];
            $combinators[] = match ($step->step_mode) {
                ApprovalStepMode::AnyOf => ApprovalGroupCombinator::AnyOf,
                ApprovalStepMode::AllOf => ApprovalGroupCombinator::AllOf,
                ApprovalStepMode::Sequential => throw new InvalidApprovalStateException(
                    'Inconsistent group: sequential mode found inside a multi-step group.'
                ),
            };
        }

        $unique = array_unique(array_map(fn (ApprovalGroupCombinator $c) => $c->value, $combinators));
        if (count($unique) > 1) {
            throw new InvalidApprovalStateException('A group cannot mix any_of and all_of modes.');
        }

        return $combinators[0] ?? ApprovalGroupCombinator::AnyOf;
    }

    /**
     * @param  Collection<int, ExpenseRequestApproval|VacationRequestApproval>  $approvals
     * @param  Collection<int, ApprovalPolicyStep>  $orderedSteps
     * @param  list<list<int>>  $stepOrderGroups
     */
    public static function firstIncompleteGroupIndex(
        Collection $approvals,
        array $stepOrderGroups,
        ?Collection $orderedSteps = null,
    ): ?int {
        foreach ($stepOrderGroups as $index => $stepOrders) {
            $combinator = $orderedSteps !== null
                ? self::groupCombinator($orderedSteps, $stepOrders)
                : ApprovalGroupCombinator::AnyOf;

            if (! self::isGroupSatisfied($approvals, $stepOrders, $combinator)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, ExpenseRequestApproval|VacationRequestApproval>  $approvals
     * @param  list<int>  $stepOrdersInGroup
     */
    public static function isGroupSatisfied(
        Collection $approvals,
        array $stepOrdersInGroup,
        ApprovalGroupCombinator $combinator = ApprovalGroupCombinator::AnyOf,
    ): bool {
        $groupApprovals = $approvals
            ->filter(fn ($a) => in_array($a->step_order, $stepOrdersInGroup, true));

        if ($groupApprovals->isEmpty()) {
            return false;
        }

        return match ($combinator) {
            ApprovalGroupCombinator::AnyOf => $groupApprovals
                ->contains(fn ($a) => $a->status === ApprovalInstanceStatus::Approved),
            ApprovalGroupCombinator::AllOf => $groupApprovals->count() === count($stepOrdersInGroup)
                && $groupApprovals->every(fn ($a) => $a->status === ApprovalInstanceStatus::Approved),
        };
    }
}
