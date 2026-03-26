<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<ApprovalPolicyStep, $this>
     */
    public function approvalPolicySteps(): HasMany
    {
        return $this->hasMany(ApprovalPolicyStep::class);
    }

    /**
     * @return HasMany<ApprovalPolicy, $this>
     */
    public function approvalPoliciesAsRequester(): HasMany
    {
        return $this->hasMany(ApprovalPolicy::class, 'requester_role_id');
    }

    /**
     * @return MorphMany<Budget, $this>
     */
    public function budgets(): MorphMany
    {
        return $this->morphMany(Budget::class, 'budgetable');
    }
}
