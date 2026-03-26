<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Region extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * @return HasMany<State, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return MorphMany<Budget, $this>
     */
    public function budgets(): MorphMany
    {
        return $this->morphMany(Budget::class, 'budgetable');
    }
}
