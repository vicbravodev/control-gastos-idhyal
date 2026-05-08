<?php

namespace App\Models;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use Database\Factories\VacationRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VacationRequestApproval extends Model
{
    /** @use HasFactory<VacationRequestApprovalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vacation_request_id',
        'step_order',
        'approver_type',
        'approver_id',
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
            'acted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VacationRequest, $this>
     */
    public function vacationRequest(): BelongsTo
    {
        return $this->belongsTo(VacationRequest::class);
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
