<?php

namespace App\Services\Budgets;

use App\Enums\BudgetLedgerEntryType;
use App\Models\BudgetLedgerEntry;
use App\Models\ExpenseRequest;
use App\Models\Payment;

/**
 * Records budget ledger rows for expense requests: {@see BudgetLedgerEntryType::Commit} when the
 * chain completes, {@see BudgetLedgerEntryType::Spend} when payment is recorded (same budget as commit).
 */
final class ExpenseRequestBudgetLedgerWriter
{
    public function __construct(
        private readonly ExpenseRequestBudgetResolver $resolver,
    ) {}

    public function recordCommitIfApplicable(ExpenseRequest $expenseRequest): void
    {
        if ($this->hasCommitEntry($expenseRequest)) {
            return;
        }

        $approved = $expenseRequest->approved_amount_cents;
        if ($approved === null || $approved <= 0) {
            return;
        }

        $budget = $this->resolver->resolve($expenseRequest);
        if ($budget === null) {
            return;
        }

        BudgetLedgerEntry::query()->create([
            'budget_id' => $budget->id,
            'entry_type' => BudgetLedgerEntryType::Commit,
            'amount_cents' => $approved,
            'source_type' => $expenseRequest->getMorphClass(),
            'source_id' => $expenseRequest->getKey(),
            'reverses_ledger_entry_id' => null,
        ]);
    }

    public function recordSpendIfApplicable(Payment $payment, ExpenseRequest $expenseRequest): void
    {
        if ($this->hasSpendEntry($payment)) {
            return;
        }

        $commit = $this->findCommitEntry($expenseRequest);
        if ($commit === null) {
            return;
        }

        BudgetLedgerEntry::query()->create([
            'budget_id' => $commit->budget_id,
            'entry_type' => BudgetLedgerEntryType::Spend,
            'amount_cents' => $payment->amount_cents,
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->getKey(),
            'reverses_ledger_entry_id' => null,
        ]);
    }

    private function hasCommitEntry(ExpenseRequest $expenseRequest): bool
    {
        return BudgetLedgerEntry::query()
            ->where('source_type', $expenseRequest->getMorphClass())
            ->where('source_id', $expenseRequest->getKey())
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->exists();
    }

    private function hasSpendEntry(Payment $payment): bool
    {
        return BudgetLedgerEntry::query()
            ->where('source_type', $payment->getMorphClass())
            ->where('source_id', $payment->getKey())
            ->where('entry_type', BudgetLedgerEntryType::Spend)
            ->exists();
    }

    private function findCommitEntry(ExpenseRequest $expenseRequest): ?BudgetLedgerEntry
    {
        return BudgetLedgerEntry::query()
            ->where('source_type', $expenseRequest->getMorphClass())
            ->where('source_id', $expenseRequest->getKey())
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->first();
    }
}
