<?php

namespace App\Models;

use Database\Factories\ExpenseConceptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseConcept extends Model
{
    /** @use HasFactory<ExpenseConceptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ExpenseConcept>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExpenseConcept>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<ExpenseRequest, $this>
     */
    public function expenseRequests(): HasMany
    {
        return $this->hasMany(ExpenseRequest::class);
    }
}
