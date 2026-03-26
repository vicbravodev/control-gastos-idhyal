<?php

namespace App\Models;

use App\Enums\ApprovalPolicyDocumentType;
use Database\Factories\ApprovalPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalPolicy extends Model
{
    /** @use HasFactory<ApprovalPolicyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'name',
        'version',
        'requester_role_id',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => ApprovalPolicyDocumentType::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function requesterRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'requester_role_id');
    }

    /**
     * @return HasMany<ApprovalPolicyStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalPolicyStep::class);
    }
}
