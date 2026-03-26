<?php

namespace App\Services\ExpenseRequests;

use App\Enums\ApprovalPolicyDocumentType;
use App\Models\ExpenseRequest;
use App\Services\Approvals\ApprovalChainValidator;
use App\Services\Approvals\ApprovalPolicyResolver;
use App\Services\Approvals\ApprovalStepGrouper;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;

/**
 * Exposes “faltan N” como grupos de pasos (AND/OR) pendientes respecto a la política activa.
 */
final class ExpenseRequestApprovalProgressResolver
{
    public function __construct(
        private readonly ApprovalPolicyResolver $resolver,
    ) {}

    /**
     * @return array{total_groups: int, remaining_groups: int, completed_groups: int}|null
     */
    public function snapshot(ExpenseRequest $expenseRequest): ?array
    {
        $expenseRequest->loadMissing(['approvals', 'user']);

        if ($expenseRequest->approvals->isEmpty()) {
            return null;
        }

        try {
            $policy = $this->resolver->resolve(
                ApprovalPolicyDocumentType::ExpenseRequest,
                $expenseRequest->user,
            );
        } catch (NoActiveApprovalPolicyException) {
            return null;
        }

        $orderedSteps = $policy->steps->sortBy('step_order')->values();
        $groups = ApprovalStepGrouper::stepOrderGroups($orderedSteps);
        $approvals = $expenseRequest->approvals->sortBy('step_order')->values();

        try {
            ApprovalChainValidator::assertApprovalsMatchPolicy($approvals, $policy);
        } catch (InvalidApprovalStateException) {
            return null;
        }

        $total = count($groups);
        if ($total === 0) {
            return null;
        }

        $firstIncomplete = ApprovalStepGrouper::firstIncompleteGroupIndex($approvals, $groups);
        $remaining = $firstIncomplete === null ? 0 : $total - $firstIncomplete;
        $completed = $total - $remaining;

        return [
            'total_groups' => $total,
            'remaining_groups' => $remaining,
            'completed_groups' => $completed,
        ];
    }
}
