<?php

namespace App\Models;

use App\Enums\BudgetStatus;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_starts_on' => 'date',
            'period_ends_on' => 'date',
            'status' => BudgetStatus::class,
            'cancelled_at' => 'datetime',
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

    /**
     * @return HasMany<BudgetAudit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(BudgetAudit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', BudgetStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === BudgetStatus::Active;
    }

    public function isCancelled(): bool
    {
        return $this->status === BudgetStatus::Cancelled;
    }
}
