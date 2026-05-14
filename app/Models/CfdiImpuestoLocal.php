<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiImpuestoLocal extends Model
{
    protected $table = 'cfdi_impuestos_locales';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_report_id',
        'clave',
        'tipo',
        'tasa',
        'importe_cents',
    ];

    /**
     * @return BelongsTo<ExpenseReport, $this>
     */
    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }
}
