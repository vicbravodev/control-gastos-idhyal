<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalPolicyDocumentType;
use App\Models\ApprovalPolicy;
use App\Models\User;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ApprovalPolicyResolver
{
    /**
     * Resolve the active approval policy for a document type and requester.
     *
     * Precedence (most specific wins):
     *   user → department → role → default (applies_to_type = null)
     *
     * Within a tier, highest version then highest id wins. If the chosen
     * policy has no steps, falls through to the next tier.
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

        $tiers = [
            $this->filterByTarget($candidates, ApprovalApproverType::User, $requester->id),
            $this->filterByTarget($candidates, ApprovalApproverType::Department, $requester->department_id),
            $this->filterByTarget($candidates, ApprovalApproverType::Role, $requester->role_id),
            $candidates->filter(fn (ApprovalPolicy $p): bool => $p->applies_to_type === null),
        ];

        foreach ($tiers as $pool) {
            if ($pool->isEmpty()) {
                continue;
            }

            /** @var ApprovalPolicy|null $policy */
            $policy = $pool->sortBy([
                ['version', 'desc'],
                ['id', 'desc'],
            ])->first();

            if ($policy !== null && $policy->steps->isNotEmpty()) {
                return $policy;
            }
        }

        throw new NoActiveApprovalPolicyException('No applicable approval policy with steps was found for this requester.');
    }

    /**
     * @param  Collection<int, ApprovalPolicy>  $candidates
     * @return Collection<int, ApprovalPolicy>
     */
    private function filterByTarget(Collection $candidates, ApprovalApproverType $type, int|string|null $id): Collection
    {
        if ($id === null) {
            return collect();
        }

        return $candidates->filter(
            fn (ApprovalPolicy $p): bool => $p->applies_to_type === $type
                && (int) $p->applies_to_id === (int) $id
        );
    }
}
