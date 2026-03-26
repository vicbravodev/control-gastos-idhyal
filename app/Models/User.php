<?php

namespace App\Models;

use App\Enums\RoleSlug;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'phone',
    'region_id',
    'state_id',
    'role_id',
    'hire_date',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'hire_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return HasMany<ExpenseRequest, $this>
     */
    public function expenseRequests(): HasMany
    {
        return $this->hasMany(ExpenseRequest::class);
    }

    /**
     * @return HasMany<VacationRequest, $this>
     */
    public function vacationRequests(): HasMany
    {
        return $this->hasMany(VacationRequest::class);
    }

    /**
     * @return HasMany<VacationEntitlement, $this>
     */
    public function vacationEntitlements(): HasMany
    {
        return $this->hasMany(VacationEntitlement::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function paymentsRecorded(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by_user_id');
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachmentsUploaded(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by_user_id');
    }

    /**
     * @return HasMany<DocumentEvent, $this>
     */
    public function documentEventsActed(): HasMany
    {
        return $this->hasMany(DocumentEvent::class, 'actor_user_id');
    }

    /**
     * @return HasMany<ExpenseRequestApproval, $this>
     */
    public function expenseRequestApprovalsAsApprover(): HasMany
    {
        return $this->hasMany(ExpenseRequestApproval::class, 'approver_user_id');
    }

    /**
     * @return HasMany<VacationRequestApproval, $this>
     */
    public function vacationRequestApprovalsAsApprover(): HasMany
    {
        return $this->hasMany(VacationRequestApproval::class, 'approver_user_id');
    }

    /**
     * @return MorphMany<Budget, $this>
     */
    public function budgets(): MorphMany
    {
        return $this->morphMany(Budget::class, 'budgetable');
    }

    public function hasRoleSlug(string $slug): bool
    {
        if ($this->role_id === null) {
            return false;
        }

        return $this->role?->slug === $slug;
    }

    public function hasRole(RoleSlug $role): bool
    {
        return $this->hasRoleSlug($role->value);
    }

    /**
     * @param  list<RoleSlug>  $roles
     */
    public function hasAnyRole(RoleSlug ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasExpenseRequestOversight(): bool
    {
        return $this->hasAnyRole(
            RoleSlug::SuperAdmin,
            RoleSlug::Contabilidad,
            RoleSlug::SecretarioGeneral,
            RoleSlug::CoordRegional,
            RoleSlug::CoordEstatal,
        );
    }

    public function hasVacationRequestOversight(): bool
    {
        return $this->hasAnyRole(
            RoleSlug::SuperAdmin,
            RoleSlug::SecretarioGeneral,
            RoleSlug::CoordRegional,
            RoleSlug::CoordEstatal,
        );
    }

    public function canManageBudgetsAndPolicies(): bool
    {
        return $this->hasAnyRole(
            RoleSlug::SuperAdmin,
            RoleSlug::Contabilidad,
            RoleSlug::SecretarioGeneral,
        );
    }
}
