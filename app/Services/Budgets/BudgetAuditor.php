<?php

namespace App\Services\Budgets;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\BudgetAudit;
use App\Models\User;

/**
 * Records BudgetAudit rows for every meaningful mutation of a Budget.
 * Audits son inmutables: solo creación, sin updates ni deletes desde el dominio.
 */
final class BudgetAuditor
{
    public function recordCreated(Budget $budget, ?User $actor): BudgetAudit
    {
        return $this->record(
            $budget,
            'created',
            [
                'amount_limit_cents' => (int) $budget->amount_limit_cents,
                'budgetable_type' => $budget->budgetable_type,
                'budgetable_id' => $budget->budgetable_id,
                'period_starts_on' => $budget->period_starts_on?->toDateString(),
                'period_ends_on' => $budget->period_ends_on?->toDateString(),
                'priority' => $budget->priority,
            ],
            null,
            $actor,
        );
    }

    public function recordAmountChanged(
        Budget $budget,
        int $fromCents,
        int $toCents,
        ?string $reason,
        ?User $actor,
    ): BudgetAudit {
        return $this->record(
            $budget,
            'amount_changed',
            [
                'from' => ['amount_limit_cents' => $fromCents],
                'to' => ['amount_limit_cents' => $toCents],
            ],
            $reason,
            $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $to
     */
    public function recordScopeChanged(
        Budget $budget,
        array $from,
        array $to,
        ?string $reason,
        ?User $actor,
    ): BudgetAudit {
        return $this->record(
            $budget,
            'scope_changed',
            [
                'from' => $from,
                'to' => $to,
            ],
            $reason,
            $actor,
        );
    }

    public function recordStatusChanged(
        Budget $budget,
        BudgetStatus $from,
        BudgetStatus $to,
        ?string $reason,
        ?User $actor,
    ): BudgetAudit {
        return $this->record(
            $budget,
            'status_changed',
            [
                'from' => ['status' => $from->value],
                'to' => ['status' => $to->value],
            ],
            $reason,
            $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function record(
        Budget $budget,
        string $event,
        array $changes,
        ?string $reason,
        ?User $actor,
    ): BudgetAudit {
        return BudgetAudit::query()->create([
            'budget_id' => $budget->getKey(),
            'event' => $event,
            'changes' => $changes,
            'reason' => $reason,
            'actor_id' => $actor?->getKey(),
        ]);
    }
}
