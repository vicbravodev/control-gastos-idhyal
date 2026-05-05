<?php

namespace Tests\Support;

use Illuminate\Support\Str;

trait CfdiTestFixtures
{
    /**
     * Genera un XML CFDI 4.0 mínimo pero realista para tests: incluye Receptor,
     * Fecha y un Timbre Fiscal Digital con UUID válido. El Total siempre coincide
     * con `$reportedAmountCents` para no romper la validación monto↔XML por defecto.
     */
    protected function cfdiXmlForReportedCents(
        int $reportedAmountCents,
        ?string $uuid = null,
        ?string $receptorRfc = null,
        ?string $fecha = null,
    ): string {
        $total = number_format($reportedAmountCents / 100, 2, '.', '');
        $uuid ??= strtoupper((string) Str::uuid());
        $receptor = $receptorRfc ?? 'IDH800514B86';
        $fechaIso = $fecha ?? now()->subDays(2)->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" '
            .'xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" '
            .'Version="4.0" Serie="A" Folio="42" '
            .'Fecha="'.htmlspecialchars($fechaIso, ENT_XML1).'" '
            .'Total="'.$total.'" SubTotal="'.$total.'" '
            .'Moneda="MXN" FormaPago="03" MetodoPago="PUE" '
            .'TipoDeComprobante="I" LugarExpedicion="66265" Exportacion="01">'
            .'<cfdi:Emisor Rfc="STK230120D23" Nombre="EMISOR DEMO" RegimenFiscal="601"/>'
            .'<cfdi:Receptor Rfc="'.$receptor.'" Nombre="DEMO" '
            .'DomicilioFiscalReceptor="64610" RegimenFiscalReceptor="601" UsoCFDI="G03"/>'
            .'<cfdi:Conceptos>'
            .'<cfdi:Concepto ClaveProdServ="81112006" Cantidad="1" '
            .'ClaveUnidad="E48" Unidad="Servicio" '
            .'Descripcion="Servicio de prueba" '
            .'ValorUnitario="'.$total.'" Importe="'.$total.'" ObjetoImp="02"/>'
            .'</cfdi:Conceptos>'
            .'<cfdi:Complemento>'
            .'<tfd:TimbreFiscalDigital Version="1.1" UUID="'.$uuid.'" '
            .'FechaTimbrado="'.htmlspecialchars($fechaIso, ENT_XML1).'" '
            .'RfcProvCertif="SAT970701NN3"/>'
            .'</cfdi:Complemento>'
            .'</cfdi:Comprobante>';
    }
}
