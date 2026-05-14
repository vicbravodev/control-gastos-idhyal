<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_user_id',
    'template_id',
    'cadence',
    'day_of_week',
    'day_of_month',
    'time_of_day',
    'tz',
    'format',
    'recipients',
    'active',
    'last_run_at',
    'next_run_at',
])]
class ReportSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
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
     * @return BelongsTo<ReportTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }
}
