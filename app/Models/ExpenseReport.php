<?php

namespace App\Models;

use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use Database\Factories\ExpenseReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpenseReport extends Model
{
    /** @use HasFactory<ExpenseReportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expense_request_id',
        'status',
        'reported_amount_cents',
        'document_type',
        'label',
        'reviewer_user_id',
        'reviewed_at',
        'submitted_at',
        'cfdi_uuid',
        'cfdi_emisor_rfc',
        'cfdi_emisor_nombre',
        'cfdi_emisor_regimen_fiscal',
        'cfdi_receptor_rfc',
        'cfdi_receptor_nombre',
        'cfdi_fecha',
        'cfdi_serie',
        'cfdi_folio',
        'cfdi_forma_pago',
        'cfdi_metodo_pago',
        'cfdi_uso_cfdi',
        'cfdi_conceptos',
        'cfdi_has_hidrocarburos_complement',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExpenseReportStatus::class,
            'document_type' => ExpenseReportDocumentType::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'cfdi_fecha' => 'datetime',
            'cfdi_conceptos' => 'array',
            'cfdi_has_hidrocarburos_complement' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ExpenseRequest, $this>
     */
    public function expenseRequest(): BelongsTo
    {
        return $this->belongsTo(ExpenseRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return HasMany<CfdiTraslado, $this>
     */
    public function cfdiTraslados(): HasMany
    {
        return $this->hasMany(CfdiTraslado::class);
    }

    /**
     * @return HasMany<CfdiRetencion, $this>
     */
    public function cfdiRetenciones(): HasMany
    {
        return $this->hasMany(CfdiRetencion::class);
    }

    /**
     * @return HasMany<CfdiImpuestoLocal, $this>
     */
    public function cfdiImpuestosLocales(): HasMany
    {
        return $this->hasMany(CfdiImpuestoLocal::class);
    }
}
