<?php

namespace Tests\Unit;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\CombineWithNext;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequestApproval;
use App\Services\Approvals\ApprovalStepGrouper;
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
            $this->policyStep(1, CombineWithNext::And),
        ]));

        $this->assertSame([[1]], $groups);
    }

    public function test_consecutive_and_splits_groups(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, CombineWithNext::And),
            $this->policyStep(2, CombineWithNext::And),
        ]));

        $this->assertSame([[1], [2]], $groups);
    }

    public function test_or_keeps_steps_in_same_group(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, CombineWithNext::Or),
            $this->policyStep(2, CombineWithNext::And),
        ]));

        $this->assertSame([[1, 2]], $groups);
    }

    public function test_mixed_and_or_pattern_from_spec(): void
    {
        $groups = ApprovalStepGrouper::stepOrderGroups(collect([
            $this->policyStep(1, CombineWithNext::And),
            $this->policyStep(2, CombineWithNext::Or),
            $this->policyStep(3, CombineWithNext::And),
            $this->policyStep(4, CombineWithNext::And),
        ]));

        $this->assertSame([[1], [2, 3], [4]], $groups);
    }

    public function test_group_satisfied_when_any_step_approved(): void
    {
        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Skipped),
        ]);

        $this->assertTrue(ApprovalStepGrouper::isGroupSatisfied($approvals, [1, 2]));
    }

    public function test_first_incomplete_group_index(): void
    {
        $approvals = collect([
            $this->approval(1, ApprovalInstanceStatus::Approved),
            $this->approval(2, ApprovalInstanceStatus::Skipped),
            $this->approval(3, ApprovalInstanceStatus::Pending),
        ]);
        $groups = [[1, 2], [3]];

        $this->assertSame(1, ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups));
    }

    private function policyStep(int $order, CombineWithNext $combine): ApprovalPolicyStep
    {
        $step = new ApprovalPolicyStep;
        $step->step_order = $order;
        $step->combine_with_next = $combine;

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
