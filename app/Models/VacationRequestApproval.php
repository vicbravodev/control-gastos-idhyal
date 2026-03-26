<?php

namespace App\Models;

use App\Enums\ApprovalInstanceStatus;
use Database\Factories\VacationRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * @return BelongsTo<VacationRequest, $this>
     */
    public function vacationRequest(): BelongsTo
    {
        return $this->belongsTo(VacationRequest::class);
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
