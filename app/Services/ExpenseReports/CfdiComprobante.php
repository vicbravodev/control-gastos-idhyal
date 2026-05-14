<?php

namespace App\Services\ExpenseReports;

use DateTimeImmutable;

/**
 * Snapshot estructurado de un CFDI 3.3/4.0 ya parseado.
 *
 * @phpstan-type CfdiConcepto array{
 *     descripcion: ?string,
 *     cantidad: ?float,
 *     unidad: ?string,
 *     valor_unitario: ?float,
 *     importe: ?float,
 *     clave_prod_serv: ?string,
 * }
 * @phpstan-type CfdiTraslado array{
 *     impuesto: string,
 *     impuesto_label: string,
 *     tipo_factor: string,
 *     tasa_o_cuota: ?float,
 *     base_cents: int,
 *     importe_cents: int,
 *     nivel: string,
 *     concepto_index: ?int,
 * }
 * @phpstan-type CfdiRetencion array{
 *     impuesto: string,
 *     impuesto_label: string,
 *     tipo_factor: ?string,
 *     tasa_o_cuota: ?float,
 *     base_cents: ?int,
 *     importe_cents: int,
 *     nivel: string,
 *     concepto_index: ?int,
 * }
 * @phpstan-type CfdiImpuestoLocal array{
 *     clave: string,
 *     tipo: string,
 *     tasa: ?float,
 *     importe_cents: int,
 * }
 */
final class CfdiComprobante
{
    /**
     * @param  list<CfdiConcepto>  $conceptos
     * @param  list<CfdiTraslado>  $traslados
     * @param  list<CfdiRetencion>  $retenciones
     * @param  list<CfdiImpuestoLocal>  $impuestosLocales
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly int $totalCents,
        public readonly string $moneda,
        public readonly string $version,
        public readonly ?string $emisorRfc,
        public readonly ?string $emisorNombre,
        public readonly ?string $emisorRegimenFiscal,
        public readonly ?string $receptorRfc,
        public readonly ?string $receptorNombre,
        public readonly ?DateTimeImmutable $fecha,
        public readonly ?string $serie,
        public readonly ?string $folio,
        public readonly ?string $formaPago,
        public readonly ?string $metodoPago,
        public readonly ?string $usoCfdi,
        public readonly array $conceptos,
        public readonly array $traslados = [],
        public readonly array $retenciones = [],
        public readonly array $impuestosLocales = [],
        public readonly bool $hasHidrocarburosComplement = false,
    ) {}
}
