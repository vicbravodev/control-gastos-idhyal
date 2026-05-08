<?php

namespace App\Models;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalPolicyDocumentType;
use Database\Factories\ApprovalPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'applies_to_type',
        'applies_to_id',
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
            'applies_to_type' => ApprovalApproverType::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function appliesTo(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ApprovalPolicyStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalPolicyStep::class);
    }
}
