<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalPolicyDocumentType;
use App\Models\ApprovalPolicy;
use App\Models\User;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ApprovalPolicyResolver
{
    /**
     * Resolve the active approval policy for a document type and requester.
     *
     * @throws NoActiveApprovalPolicyException
     */
    public function resolve(
        ApprovalPolicyDocumentType $documentType,
        User $requester,
        ?CarbonInterface $asOf = null,
    ): ApprovalPolicy {
        $asOf ??= Carbon::now();
        $asOfDate = $asOf->toDateString();

        $candidates = ApprovalPolicy::query()
            ->where('document_type', $documentType->value)
            ->where('is_active', true)
            ->where(function ($query) use ($asOfDate): void {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $asOfDate);
            })
            ->where(function ($query) use ($asOfDate): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $asOfDate);
            })
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->get();

        if ($candidates->isEmpty()) {
            throw new NoActiveApprovalPolicyException('No active approval policy matches this document type and date.');
        }

        $requesterRoleId = $requester->role_id;

        $specific = $candidates->filter(
            fn (ApprovalPolicy $policy): bool => $policy->requester_role_id !== null
                && $policy->requester_role_id === $requesterRoleId
        );

        $pool = $specific->isNotEmpty()
            ? $specific
            : $candidates->filter(fn (ApprovalPolicy $policy): bool => $policy->requester_role_id === null);

        if ($pool->isEmpty()) {
            throw new NoActiveApprovalPolicyException('No default approval policy exists for this document type.');
        }

        /** @var ApprovalPolicy $policy */
        $policy = $pool->sortBy([
            ['version', 'desc'],
            ['id', 'desc'],
        ])->first();

        if ($policy->steps->isEmpty()) {
            throw new NoActiveApprovalPolicyException('The resolved approval policy has no steps.');
        }

        return $policy;
    }
}
