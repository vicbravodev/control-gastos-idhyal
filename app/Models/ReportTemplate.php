<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_user_id',
    'slug',
    'name',
    'description',
    'icon',
    'view',
    'group_by',
    'filters',
    'is_built_in',
    'is_shared',
])]
class ReportTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_built_in' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<ReportSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'template_id');
    }
}
