<?php

namespace App\Models;

use App\Enums\ExpenseReportStatus;
use Database\Factories\ExpenseReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpenseReport extends Model
{
    /** @use HasFactory<ExpenseReportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_request_id',
        'status',
        'reported_amount_cents',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExpenseReportStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExpenseRequest, $this>
     */
    public function expenseRequest(): BelongsTo
    {
        return $this->belongsTo(ExpenseRequest::class);
    }

    /**
     * @return HasOne<Settlement, $this>
     */
    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
