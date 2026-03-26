<?php

namespace App\Models;

use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Settlement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_report_id',
        'status',
        'basis_amount_cents',
        'reported_amount_cents',
        'difference_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SettlementStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ExpenseReport, $this>
     */
    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
