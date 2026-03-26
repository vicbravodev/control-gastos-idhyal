<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\ExpenseRequestStatus;
use Database\Factories\ExpenseRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpenseRequest extends Model
{
    /** @use HasFactory<ExpenseRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'folio',
        'requested_amount_cents',
        'approved_amount_cents',
        'expense_concept_id',
        'concept_description',
        'delivery_method',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExpenseRequestStatus::class,
            'delivery_method' => DeliveryMethod::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExpenseConcept, $this>
     */
    public function expenseConcept(): BelongsTo
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function conceptLabel(): string
    {
        return $this->expenseConcept?->name ?? '';
    }

    /**
     * @return HasMany<ExpenseRequestApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ExpenseRequestApproval::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<ExpenseReport, $this>
     */
    public function expenseReport(): HasOne
    {
        return $this->hasOne(ExpenseReport::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return MorphMany<DocumentEvent, $this>
     */
    public function documentEvents(): MorphMany
    {
        return $this->morphMany(DocumentEvent::class, 'subject');
    }
}
