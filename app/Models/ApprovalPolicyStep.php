<?php

namespace App\Models;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalStepMode;
use Database\Factories\ApprovalPolicyStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'approver_type',
        'approver_id',
        'step_mode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approver_type' => ApprovalApproverType::class,
            'step_mode' => ApprovalStepMode::class,
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
     * @return MorphTo<Model, $this>
     */
    public function approver(): MorphTo
    {
        return $this->morphTo();
    }
}
