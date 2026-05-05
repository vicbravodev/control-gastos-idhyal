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
 */
final class CfdiComprobante
{
    /**
     * @param  list<CfdiConcepto>  $conceptos
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly int $totalCents,
        public readonly string $moneda,
        public readonly string $version,
        public readonly ?string $emisorRfc,
        public readonly ?string $emisorNombre,
        public readonly ?string $receptorRfc,
        public readonly ?string $receptorNombre,
        public readonly ?DateTimeImmutable $fecha,
        public readonly ?string $serie,
        public readonly ?string $folio,
        public readonly ?string $formaPago,
        public readonly ?string $metodoPago,
        public readonly ?string $usoCfdi,
        public readonly array $conceptos,
    ) {}
}
