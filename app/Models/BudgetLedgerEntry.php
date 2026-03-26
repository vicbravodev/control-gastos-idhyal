<?php

namespace App\Models;

use App\Enums\BudgetLedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BudgetLedgerEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_id',
        'entry_type',
        'amount_cents',
        'source_type',
        'source_id',
        'reverses_ledger_entry_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_type' => BudgetLedgerEntryType::class,
        ];
    }

    /**
     * @return BelongsTo<Budget, $this>
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<BudgetLedgerEntry, $this>
     */
    public function reversesLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(BudgetLedgerEntry::class, 'reverses_ledger_entry_id');
    }
}
