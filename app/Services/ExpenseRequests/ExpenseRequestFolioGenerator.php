<?php

namespace App\Services\ExpenseRequests;

use App\Models\ExpenseRequest;

/**
 * Human-readable folios for expense requests: EXP-{year}-{id} (fits 64-char column).
 */
final class ExpenseRequestFolioGenerator
{
    public function assign(ExpenseRequest $expenseRequest): void
    {
        $expenseRequest->forceFill([
            'folio' => sprintf('EXP-%d-%d', now()->year, $expenseRequest->getKey()),
        ])->save();
    }
}
