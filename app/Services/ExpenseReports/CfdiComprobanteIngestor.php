<?php

namespace App\Services\ExpenseReports;

use App\Models\CfdiImpuestoLocal;
use App\Models\CfdiRetencion;
use App\Models\CfdiTraslado;
use App\Models\ExpenseReport;
use App\Services\ExpenseReports\Exceptions\DuplicateCfdiException;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use Illuminate\Http\UploadedFile;

/**
 * Centraliza el flujo de ingesta de un XML CFDI para una comprobación:
 *  1. Parsea el XML y devuelve el DTO.
 *  2. Aplica el monto del CFDI cuando el reportado viene en cero (auto-fill).
 *  3. Valida reglas de negocio (total↔monto, RFC del receptor, rango de fecha).
 *  4. Verifica que el UUID no se haya comprobado antes.
 */
final class CfdiComprobanteIngestor
{
    public function __construct(
        private readonly CfdiComprobanteValidator $validator,
    ) {}

    /**
     * @throws InvalidExpenseReportException
     * @throws DuplicateCfdiException
     */
    public function ingest(
        UploadedFile $xml,
        int $reportedAmountCents,
        ?ExpenseReport $excludeReport = null,
        bool $enforceBusinessRules = true,
    ): CfdiComprobanteIngestionResult {
        $cfdi = $this->validator->parse($xml);

        $resolvedAmount = $reportedAmountCents > 0
            ? $reportedAmountCents
            : $cfdi->totalCents;

        if ($enforceBusinessRules) {
            $this->validator->validateBusinessRules($cfdi, $resolvedAmount);
        }

        if ($cfdi->uuid !== null) {
            $this->assertNotDuplicated($cfdi->uuid, $excludeReport);
        }

        return new CfdiComprobanteIngestionResult($cfdi, $resolvedAmount);
    }

    /**
     * Aplica la metadata del CFDI a un ExpenseReport (sin guardar).
     *
     * @return array<string, mixed> Atributos cfdi_* listos para `update()`.
     */
    public function metadataAttributes(CfdiComprobante $cfdi): array
    {
        return [
            'cfdi_uuid' => $cfdi->uuid,
            'cfdi_emisor_rfc' => $cfdi->emisorRfc,
            'cfdi_emisor_nombre' => $cfdi->emisorNombre,
            'cfdi_emisor_regimen_fiscal' => $cfdi->emisorRegimenFiscal,
            'cfdi_receptor_rfc' => $cfdi->receptorRfc,
            'cfdi_receptor_nombre' => $cfdi->receptorNombre,
            'cfdi_fecha' => $cfdi->fecha,
            'cfdi_serie' => $cfdi->serie,
            'cfdi_folio' => $cfdi->folio,
            'cfdi_forma_pago' => $cfdi->formaPago,
            'cfdi_metodo_pago' => $cfdi->metodoPago,
            'cfdi_uso_cfdi' => $cfdi->usoCfdi,
            'cfdi_conceptos' => $cfdi->conceptos,
            'cfdi_has_hidrocarburos_complement' => $cfdi->hasHidrocarburosComplement,
        ];
    }

    /**
     * Persist the parsed impuesto rows for a given report. Replaces any
     * prior rows (idempotent on re-ingest of the same report).
     */
    public function persistImpuestos(ExpenseReport $report, CfdiComprobante $cfdi): void
    {
        CfdiTraslado::query()->where('expense_report_id', $report->id)->delete();
        CfdiRetencion::query()->where('expense_report_id', $report->id)->delete();
        CfdiImpuestoLocal::query()->where('expense_report_id', $report->id)->delete();

        foreach ($cfdi->traslados as $row) {
            CfdiTraslado::query()->create(array_merge($row, [
                'expense_report_id' => $report->id,
            ]));
        }

        foreach ($cfdi->retenciones as $row) {
            CfdiRetencion::query()->create(array_merge($row, [
                'expense_report_id' => $report->id,
            ]));
        }

        foreach ($cfdi->impuestosLocales as $row) {
            CfdiImpuestoLocal::query()->create(array_merge($row, [
                'expense_report_id' => $report->id,
            ]));
        }
    }

    /**
     * @throws DuplicateCfdiException
     */
    private function assertNotDuplicated(string $uuid, ?ExpenseReport $excludeReport): void
    {
        $query = ExpenseReport::query()->where('cfdi_uuid', $uuid);
        if ($excludeReport !== null && $excludeReport->exists) {
            $query->whereKeyNot($excludeReport->getKey());
        }

        $existing = $query->with('expenseRequest:id,folio')->first();
        if ($existing === null) {
            return;
        }

        $folio = $existing->expenseRequest?->folio ?? '#'.$existing->expense_request_id;

        throw new DuplicateCfdiException(__(
            'Esta factura ya fue comprobada en la solicitud :folio (UUID :uuid).',
            ['folio' => $folio, 'uuid' => $uuid],
        ));
    }
}
