<?php

namespace Tests\Unit;

use App\Enums\ApprovalGroupCombinator;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequestApproval;
use App\Services\Approvals\ApprovalStepGrouper;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use Tests\TestCase;

class ApprovalStepGrouperTest extends TestCase
{
    public function test_empty_steps_yield_empty_groups(): void
    {
        $this->assertSame([], ApprovalStepGrouper::stepOrderGroups(collect()));
    }

    public function test_single_step_one_group(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, ApprovalStepMode::Sequential),
        ]));

        $this->assertSame([[1]], $groups);
    }

    public function test_consecutive_sequential_splits_groups(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, ApprovalStepMode::Sequential),
            $this->policyStep(2, ApprovalStepMode::Sequential),
        ]));

        $this->assertSame([[1], [2]], $groups);
    }

    public function test_any_of_keeps_steps_in_same_group(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, ApprovalStepMode::AnyOf),
            $this->policyStep(2, ApprovalStepMode::Sequential),
        ]));

        $this->assertSame([[1, 2]], $groups);
    }

    public function test_all_of_keeps_steps_in_same_group(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, ApprovalStepMode::AllOf),
            $this->policyStep(2, ApprovalStepMode::Sequential),
        ]));

        $this->assertSame([[1, 2]], $groups);
    }

    public function test_mixed_pattern_from_spec(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, ApprovalStepMode::Sequential),
            $this->policyStep(2, ApprovalStepMode::AnyOf),
            $this->policyStep(3, ApprovalStepMode::Sequential),
            $this->policyStep(4, ApprovalStepMode::Sequential),
        ]));

        $this->assertSame([[1], [2, 3], [4]], $groups);
    }

    public function test_any_of_group_satisfied_when_any_step_approved(): void
    {
        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Skipped),
        ]);

        $this->assertTrue(ApprovalStepGrouper::isGroupSatisfied(
            $approvals,
            [1, 2],
            ApprovalGroupCombinator::AnyOf,
        ));
    }

    public function test_all_of_group_requires_every_step_approved(): void
    {
        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Pending),
        ]);

        $this->assertFalse(ApprovalStepGrouper::isGroupSatisfied(
            $approvals,
            [1, 2],
            ApprovalGroupCombinator::AllOf,
        ));

        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Approved),
        ]);

        $this->assertTrue(ApprovalStepGrouper::isGroupSatisfied(
            $approvals,
            [1, 2],
            ApprovalGroupCombinator::AllOf,
        ));
    }

    public function test_group_combinator_rejects_mixed_modes(): void
    {
        $steps = collect([
            $this->policyStep(1, ApprovalStepMode::AnyOf),
            $this->policyStep(2, ApprovalStepMode::AllOf),
            $this->policyStep(3, ApprovalStepMode::Sequential),
        ]);

        $this->expectException(InvalidApprovalStateException::class);
        ApprovalStepGrouper::groupCombinator($steps, [1, 2, 3]);
    }

    public function test_first_incomplete_group_index(): void
    {
        $orderedSteps = collect([
            $this->policyStep(1, ApprovalStepMode::AnyOf),
            $this->policyStep(2, ApprovalStepMode::Sequential),
            $this->policyStep(3, ApprovalStepMode::Sequential),
        ]);
        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Skipped),
            $this->approval(3, ApprovalInstanceStatus::Pending),
        ]);
        $groups = [[1, 2], [3]];

        $this->assertSame(1, ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups, $orderedSteps));
    }

    private function policyStep(int $order, ApprovalStepMode $mode): ApprovalPolicyStep
    {
        $step = new ApprovalPolicyStep;
        $step->step_order = $order;
        $step->step_mode = $mode;

        return $step;
    }

    private function approval(int $stepOrder, ApprovalInstanceStatus $status): ExpenseRequestApproval
    {
        $a = new ExpenseRequestApproval;
        $a->step_order = $stepOrder;
        $a->status = $status;

        return $a;
    }
}
