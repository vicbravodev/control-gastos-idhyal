<?php

namespace App\Models;

use Database\Factories\VacationEntitlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationEntitlement extends Model
{
    /** @use HasFactory<VacationEntitlementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'calendar_year',
        'days_allocated',
        'days_used',
        'vacation_rule_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<VacationRule, $this>
     */
    public function vacationRule(): BelongsTo
    {
        return $this->belongsTo(VacationRule::class);
    }
}
