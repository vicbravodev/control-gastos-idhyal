<?php

namespace App\Models;

use App\Enums\CombineWithNext;
use Database\Factories\ApprovalPolicyStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalPolicyStep extends Model
{
    /** @use HasFactory<ApprovalPolicyStepFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'approval_policy_id',
        'step_order',
        'role_id',
        'combine_with_next',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'combine_with_next' => CombineWithNext::class,
        ];
    }

    /**
     * @return BelongsTo<ApprovalPolicy, $this>
     */
    public function approvalPolicy(): BelongsTo
    {
        return $this->belongsTo(ApprovalPolicy::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
