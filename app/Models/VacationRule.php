<?php

namespace App\Models;

use Database\Factories\VacationRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VacationRule extends Model
{
    /** @use HasFactory<VacationRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'min_years_service',
        'max_years_service',
        'days_granted_per_year',
        'max_days_per_request',
        'max_days_per_month',
        'max_days_per_quarter',
        'max_days_per_year',
        'blackout_dates',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_years_service' => 'decimal:1',
            'max_years_service' => 'decimal:1',
            'blackout_dates' => 'array',
        ];
    }

    /**
     * @return HasMany<VacationEntitlement, $this>
     */
    public function vacationEntitlements(): HasMany
    {
        return $this->hasMany(VacationEntitlement::class);
    }
}
