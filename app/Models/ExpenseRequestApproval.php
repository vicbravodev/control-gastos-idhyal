<?php

namespace App\Models;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\ExpenseRequestApprovalReason;
use Database\Factories\ExpenseRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpenseRequestApproval extends Model
{
    /** @use HasFactory<ExpenseRequestApprovalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_request_id',
        'step_order',
        'approver_type',
        'approver_id',
        'reason',
        'status',
        'approver_user_id',
        'note',
        'acted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approver_type' => ApprovalApproverType::class,
            'status' => ApprovalInstanceStatus::class,
            'reason' => ExpenseRequestApprovalReason::class,
            'acted_at' => 'datetime',
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
     * The expected approver target (Role | Department | User).
     *
     * @return MorphTo<Model, $this>
     */
    public function approver(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who actually approved/rejected this step (if any).
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
