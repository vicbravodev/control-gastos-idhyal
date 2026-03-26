<?php

namespace App\Models;

use App\Enums\ApprovalInstanceStatus;
use Database\Factories\ExpenseRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'role_id',
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
            'status' => ApprovalInstanceStatus::class,
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
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
