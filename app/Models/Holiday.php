<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'name',
        'description',
    ];

    /**
     * Stored as Y-m-d (no time component) so unique-index comparisons in SQLite
     * line up with input strings; exposed as a CarbonImmutable for callers.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : Carbon::parse($value)->toImmutable(),
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->format('Y-m-d'),
        );
    }
}
