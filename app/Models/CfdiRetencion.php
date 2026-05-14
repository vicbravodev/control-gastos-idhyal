<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiRetencion extends Model
{
    protected $table = 'cfdi_retenciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_report_id',
        'impuesto',
        'impuesto_label',
        'tipo_factor',
        'tasa_o_cuota',
        'base_cents',
        'importe_cents',
        'nivel',
        'concepto_index',
    ];

    /**
     * @return BelongsTo<ExpenseReport, $this>
     */
    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }
}
