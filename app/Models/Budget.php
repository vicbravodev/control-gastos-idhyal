<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budgetable_type',
        'budgetable_id',
        'period_starts_on',
        'period_ends_on',
        'amount_limit_cents',
        'priority',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_starts_on' => 'date',
            'period_ends_on' => 'date',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function budgetable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<BudgetLedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BudgetLedgerEntry::class);
    }
}
