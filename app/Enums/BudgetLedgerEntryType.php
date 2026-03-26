<?php

namespace App\Enums;

/**
 * budget_ledger_entries.entry_type (data-dictionary-stage2).
 */
enum BudgetLedgerEntryType: string
{
    case Commit = 'commit';

    case Spend = 'spend';

    case Reverse = 'reverse';

    case Adjust = 'adjust';
}
