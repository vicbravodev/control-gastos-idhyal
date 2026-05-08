<?php

namespace App\Services\Approvals;

use App\Models\ApprovalPolicy;
use App\Models\ExpenseRequestApproval;
use App\Models\VacationRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use Illuminate\Support\Collection;

final class ApprovalChainValidator
{
    /**
     * @param  Collection<int, ExpenseRequestApproval|VacationRequestApproval>  $approvals
     *
     * @throws InvalidApprovalStateException
     */
    public static function assertApprovalsMatchPolicy(Collection $approvals, ApprovalPolicy $policy): void
    {
        $steps = $policy->steps->sortBy('step_order')->values();

        if ($approvals->count() !== $steps->count()) {
            throw new InvalidApprovalStateException('Approval instance count does not match policy steps.');
        }

        foreach ($steps as $step) {
            $approval = $approvals->firstWhere('step_order', $step->step_order);
            if ($approval === null
                || $approval->approver_type !== $step->approver_type
                || (int) $approval->approver_id !== (int) $step->approver_id
            ) {
                throw new InvalidApprovalStateException('Approval rows do not match the current policy steps.');
            }
        }
    }
}
