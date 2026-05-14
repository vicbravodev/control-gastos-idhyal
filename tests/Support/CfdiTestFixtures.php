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

    /**
     * CFDI 4.0 de gasolina con IEPS (Cuota) y complemento de hidrocarburos.
     * Total = 370.65 (311.65 subtotal + 49.86 IVA + 9.14 IEPS).
     */
    protected function cfdiXmlGasolinaIeps(?string $uuid = null): string
    {
        $uuid ??= strtoupper((string) \Illuminate\Support\Str::uuid());
        $fechaIso = now()->subDays(2)->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" '
            .'xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" '
            .'xmlns:hidrocarburospetroliferos="http://www.sat.gob.mx/hidrocarburospetroliferos" '
            .'Version="4.0" Serie="A" Folio="9124" '
            .'Fecha="'.htmlspecialchars($fechaIso, ENT_XML1).'" '
            .'SubTotal="311.65" Total="370.65" Moneda="MXN" '
            .'FormaPago="28" MetodoPago="PUE" TipoDeComprobante="I" '
            .'LugarExpedicion="85835" Exportacion="01">'
            .'<cfdi:Emisor Rfc="SAL9210091H5" Nombre="SERVICIO ALAMEDA" RegimenFiscal="601"/>'
            .'<cfdi:Receptor Rfc="IDH800514B86" Nombre="IDHYAL" '
            .'DomicilioFiscalReceptor="64610" RegimenFiscalReceptor="601" UsoCFDI="G03"/>'
            .'<cfdi:Conceptos>'
            .'<cfdi:Concepto Cantidad="15.450188" ClaveProdServ="15101514" ClaveUnidad="LTR" '
            .'Descripcion="MAGNA" Importe="311.650269" ObjetoImp="02" Unidad="LITRO" ValorUnitario="20.171293">'
            .'<cfdi:Impuestos>'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="311.650269" Importe="49.864043" Impuesto="002" TasaOCuota="0.160000" TipoFactor="Tasa"/>'
            .'<cfdi:Traslado Base="15.450188" Importe="9.135696" Impuesto="003" TasaOCuota="0.591300" TipoFactor="Cuota"/>'
            .'</cfdi:Traslados>'
            .'</cfdi:Impuestos>'
            .'<cfdi:ComplementoConcepto>'
            .'<hidrocarburospetroliferos:HidroYPetro ClaveHYP="15101514" NumeroPermiso="PL/4950/EXP/ES/2015" SubProductoHYP="SP16" TipoPermiso="PER01" Version="1.0"/>'
            .'</cfdi:ComplementoConcepto>'
            .'</cfdi:Concepto>'
            .'</cfdi:Conceptos>'
            .'<cfdi:Impuestos TotalImpuestosTrasladados="59.00">'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="311.65" Importe="49.86" Impuesto="002" TasaOCuota="0.160000" TipoFactor="Tasa"/>'
            .'<cfdi:Traslado Base="15.45" Importe="9.14" Impuesto="003" TasaOCuota="0.591300" TipoFactor="Cuota"/>'
            .'</cfdi:Traslados>'
            .'</cfdi:Impuestos>'
            .'<cfdi:Complemento>'
            .'<tfd:TimbreFiscalDigital Version="1.1" UUID="'.$uuid.'" '
            .'FechaTimbrado="'.htmlspecialchars($fechaIso, ENT_XML1).'" RfcProvCertif="SAT970701NN3"/>'
            .'</cfdi:Complemento>'
            .'</cfdi:Comprobante>';
    }

    /**
     * CFDI 4.0 de hospedaje con ISH (impuesto local 3%).
     * Total = 2827.44 (2376 subtotal + 380.16 IVA + 71.28 ISH).
     */
    protected function cfdiXmlHospedajeIsh(?string $uuid = null): string
    {
        $uuid ??= strtoupper((string) \Illuminate\Support\Str::uuid());
        $fechaIso = now()->subDays(2)->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" '
            .'xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" '
            .'xmlns:implocal="http://www.sat.gob.mx/implocal" '
            .'Version="4.0" Serie="A" Folio="66191" '
            .'Fecha="'.htmlspecialchars($fechaIso, ENT_XML1).'" '
            .'SubTotal="2376.00" Total="2827.44" Moneda="MXN" '
            .'FormaPago="03" MetodoPago="PUE" TipoDeComprobante="I" '
            .'LugarExpedicion="28014" Exportacion="01">'
            .'<cfdi:Emisor Rfc="OHC1108082LA" Nombre="OPERADORA HOTELERA CAMINO REAL" RegimenFiscal="601"/>'
            .'<cfdi:Receptor Rfc="IDH800514B86" Nombre="IDHYAL" '
            .'DomicilioFiscalReceptor="64610" RegimenFiscalReceptor="601" UsoCFDI="G03"/>'
            .'<cfdi:Conceptos>'
            .'<cfdi:Concepto Cantidad="1.00" ClaveProdServ="90111800" ClaveUnidad="E48" '
            .'Descripcion="RENTA DE HABITACION" Importe="2376.00" ObjetoImp="02" Unidad="SERVICIO" ValorUnitario="2376.00">'
            .'<cfdi:Impuestos>'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="2376.00" Importe="380.16" Impuesto="002" TasaOCuota="0.160000" TipoFactor="Tasa"/>'
            .'</cfdi:Traslados>'
            .'</cfdi:Impuestos>'
            .'</cfdi:Concepto>'
            .'</cfdi:Conceptos>'
            .'<cfdi:Impuestos TotalImpuestosTrasladados="380.16">'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="2376.00" Importe="380.16" Impuesto="002" TasaOCuota="0.160000" TipoFactor="Tasa"/>'
            .'</cfdi:Traslados>'
            .'</cfdi:Impuestos>'
            .'<cfdi:Complemento>'
            .'<implocal:ImpuestosLocales TotaldeRetenciones="0.00" TotaldeTraslados="71.28" version="1.0">'
            .'<implocal:TrasladosLocales ImpLocTrasladado="ISH" Importe="71.28" TasadeTraslado="3.00"/>'
            .'</implocal:ImpuestosLocales>'
            .'<tfd:TimbreFiscalDigital Version="1.1" UUID="'.$uuid.'" '
            .'FechaTimbrado="'.htmlspecialchars($fechaIso, ENT_XML1).'" RfcProvCertif="SAT970701NN3"/>'
            .'</cfdi:Complemento>'
            .'</cfdi:Comprobante>';
    }

    /**
     * CFDI 4.0 de RESICO (RegimenFiscal=626) con retención de ISR.
     * Total = 340 (318.50 + 25.48 IVA 8% - 3.98 retención ISR 1.25%).
     */
    protected function cfdiXmlResicoRetencionIsr(?string $uuid = null): string
    {
        $uuid ??= strtoupper((string) \Illuminate\Support\Str::uuid());
        $fechaIso = now()->subDays(2)->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" '
            .'xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" '
            .'Version="4.0" Serie="A" Folio="1" '
            .'Fecha="'.htmlspecialchars($fechaIso, ENT_XML1).'" '
            .'SubTotal="318.50" Total="340.00" Moneda="MXN" '
            .'FormaPago="28" MetodoPago="PUE" TipoDeComprobante="I" '
            .'LugarExpedicion="21410" Exportacion="01">'
            .'<cfdi:Emisor Rfc="DUSC721208HP8" Nombre="CONCEPCION DUMAS SOLIS" RegimenFiscal="626"/>'
            .'<cfdi:Receptor Rfc="IDH800514B86" Nombre="IDHYAL" '
            .'DomicilioFiscalReceptor="64610" RegimenFiscalReceptor="601" UsoCFDI="G03"/>'
            .'<cfdi:Conceptos>'
            .'<cfdi:Concepto ClaveProdServ="90101501" Cantidad="1.00" ClaveUnidad="E48" '
            .'Descripcion="Consumo de alimentos" ValorUnitario="318.5011" Importe="318.501100" ObjetoImp="02">'
            .'<cfdi:Impuestos>'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="318.501100" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.080000" Importe="25.480088"/>'
            .'</cfdi:Traslados>'
            .'<cfdi:Retenciones>'
            .'<cfdi:Retencion Base="318.501100" Impuesto="001" TipoFactor="Tasa" TasaOCuota="0.012500" Importe="3.981264"/>'
            .'</cfdi:Retenciones>'
            .'</cfdi:Impuestos>'
            .'</cfdi:Concepto>'
            .'</cfdi:Conceptos>'
            .'<cfdi:Impuestos TotalImpuestosTrasladados="25.48" TotalImpuestosRetenidos="3.98">'
            .'<cfdi:Retenciones>'
            .'<cfdi:Retencion Impuesto="001" Importe="3.98"/>'
            .'</cfdi:Retenciones>'
            .'<cfdi:Traslados>'
            .'<cfdi:Traslado Base="318.50" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.080000" Importe="25.48"/>'
            .'</cfdi:Traslados>'
            .'</cfdi:Impuestos>'
            .'<cfdi:Complemento>'
            .'<tfd:TimbreFiscalDigital Version="1.1" UUID="'.$uuid.'" '
            .'FechaTimbrado="'.htmlspecialchars($fechaIso, ENT_XML1).'" RfcProvCertif="SAT970701NN3"/>'
            .'</cfdi:Complemento>'
            .'</cfdi:Comprobante>';
    }
}
