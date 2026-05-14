<?php

namespace Tests\Unit;

use App\Services\ExpenseReports\CfdiComprobanteValidator;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\Support\CfdiTestFixtures;
use Tests\TestCase;

class CfdiComprobanteValidatorTest extends TestCase
{
    use CfdiTestFixtures;

    private CfdiComprobanteValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CfdiComprobanteValidator;
        Config::set('expense_reports.cfdi.validate_structure', true);
        Config::set('expense_reports.cfdi.require_total_matches_reported', true);
        Config::set('expense_reports.cfdi.total_match_tolerance_cents', 2);
        Config::set('expense_reports.cfdi.require_moneda_mxn', true);
    }

    public function test_skips_business_rules_when_disabled_in_config(): void
    {
        Config::set('expense_reports.cfdi.validate_structure', false);

        // El XML sigue parseándose (necesitamos el Total para auto-fill y la
        // metadata), pero las reglas de negocio (total↔monto, moneda, RFC,
        // rango de fecha) no se aplican.
        $xml = $this->cfdiXmlString(4, '500.00', 'USD');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $cfdi = $this->validator->validate($file, 999_999);

        $this->assertSame(50_000, $cfdi->totalCents);
        $this->assertSame('USD', $cfdi->moneda);
    }

    public function test_accepts_cfdi_4_comprobante_with_matching_total(): void
    {
        $xml = $this->cfdiXmlString(4, '1000.00', 'MXN');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 100_000);

        $this->assertTrue(true);
    }

    public function test_accepts_cfdi_3_comprobante_with_matching_total(): void
    {
        $xml = $this->cfdiXmlString(3, '950.50', 'MXN');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 95_050);

        $this->assertTrue(true);
    }

    public function test_accepts_default_namespace_comprobante(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Comprobante xmlns="http://www.sat.gob.mx/cfd/4" Version="4.0" Total="10.00" Moneda="MXN"/>';
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 1_000);

        $this->assertTrue(true);
    }

    public function test_rejects_non_cfdi_root(): void
    {
        $file = UploadedFile::fake()->createWithContent('c.xml', '<?xml version="1.0"?><Factura/>');

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 100);
    }

    public function test_rejects_comprobante_in_wrong_namespace(): void
    {
        $xml = '<?xml version="1.0"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://example.com/fake" Version="1.0" Total="1.00" Moneda="MXN"/>';
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 100);
    }

    public function test_rejects_malformed_xml(): void
    {
        $file = UploadedFile::fake()->createWithContent('c.xml', 'not xml');

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 100);
    }

    public function test_rejects_missing_total(): void
    {
        $xml = '<?xml version="1.0"?>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" Moneda="MXN"/>';
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 100);
    }

    public function test_rejects_non_mxn_when_required(): void
    {
        $xml = $this->cfdiXmlString(4, '100.00', 'USD');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 10_000);
    }

    public function test_allows_non_mxn_when_not_required(): void
    {
        Config::set('expense_reports.cfdi.require_moneda_mxn', false);
        $xml = $this->cfdiXmlString(4, '100.00', 'USD');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 10_000);

        $this->assertTrue(true);
    }

    public function test_rejects_total_mismatch_beyond_tolerance(): void
    {
        $xml = $this->cfdiXmlString(4, '1000.00', 'MXN');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->expectException(InvalidExpenseReportException::class);
        $this->validator->validate($file, 99_900);
    }

    public function test_allows_total_within_tolerance(): void
    {
        Config::set('expense_reports.cfdi.total_match_tolerance_cents', 5);
        $xml = $this->cfdiXmlString(4, '1000.00', 'MXN');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 100_003);

        $this->assertTrue(true);
    }

    public function test_skips_total_match_when_disabled(): void
    {
        Config::set('expense_reports.cfdi.require_total_matches_reported', false);
        $xml = $this->cfdiXmlString(4, '1000.00', 'MXN');
        $file = UploadedFile::fake()->createWithContent('c.xml', $xml);

        $this->validator->validate($file, 50_000);

        $this->assertTrue(true);
    }

    public function test_parses_gasolina_ieps_and_marks_hidrocarburos_complement(): void
    {
        Config::set('expense_reports.cfdi.require_total_matches_reported', false);
        Config::set('expense_reports.cfdi.validate_fecha_range', false);

        $xml = $this->cfdiXmlGasolinaIeps();
        $file = UploadedFile::fake()->createWithContent('gas.xml', $xml);

        $cfdi = $this->validator->validate($file, 0);

        $this->assertSame('601', $cfdi->emisorRegimenFiscal);
        $this->assertTrue($cfdi->hasHidrocarburosComplement);

        $byImpuestoNivel = [];
        foreach ($cfdi->traslados as $t) {
            $byImpuestoNivel[$t['impuesto'].':'.$t['nivel']] = $t;
        }

        $this->assertArrayHasKey('002:document', $byImpuestoNivel);
        $this->assertSame('IVA', $byImpuestoNivel['002:document']['impuesto_label']);
        $this->assertSame('Tasa', $byImpuestoNivel['002:document']['tipo_factor']);
        $this->assertSame(4986, $byImpuestoNivel['002:document']['importe_cents']);

        $this->assertArrayHasKey('003:document', $byImpuestoNivel);
        $this->assertSame('IEPS', $byImpuestoNivel['003:document']['impuesto_label']);
        $this->assertSame('Cuota', $byImpuestoNivel['003:document']['tipo_factor']);
        $this->assertSame(914, $byImpuestoNivel['003:document']['importe_cents']);
    }

    public function test_parses_hospedaje_ish_impuesto_local(): void
    {
        Config::set('expense_reports.cfdi.require_total_matches_reported', false);
        Config::set('expense_reports.cfdi.validate_fecha_range', false);

        $xml = $this->cfdiXmlHospedajeIsh();
        $file = UploadedFile::fake()->createWithContent('h.xml', $xml);

        $cfdi = $this->validator->validate($file, 0);

        $this->assertCount(1, $cfdi->impuestosLocales);
        $this->assertSame('ISH', $cfdi->impuestosLocales[0]['clave']);
        $this->assertSame('traslado', $cfdi->impuestosLocales[0]['tipo']);
        $this->assertSame(7128, $cfdi->impuestosLocales[0]['importe_cents']);
        $this->assertEqualsWithDelta(0.03, $cfdi->impuestosLocales[0]['tasa'], 0.0001);
        $this->assertFalse($cfdi->hasHidrocarburosComplement);
    }

    public function test_parses_resico_retencion_isr(): void
    {
        Config::set('expense_reports.cfdi.require_total_matches_reported', false);
        Config::set('expense_reports.cfdi.validate_fecha_range', false);

        $xml = $this->cfdiXmlResicoRetencionIsr();
        $file = UploadedFile::fake()->createWithContent('r.xml', $xml);

        $cfdi = $this->validator->validate($file, 0);

        $this->assertSame('626', $cfdi->emisorRegimenFiscal);
        $this->assertNotEmpty($cfdi->retenciones);

        $documentRetenciones = array_values(array_filter(
            $cfdi->retenciones,
            fn (array $r): bool => $r['nivel'] === 'document',
        ));
        $this->assertCount(1, $documentRetenciones);
        $this->assertSame('001', $documentRetenciones[0]['impuesto']);
        $this->assertSame('ISR', $documentRetenciones[0]['impuesto_label']);
        $this->assertSame(398, $documentRetenciones[0]['importe_cents']);

        $conceptRetenciones = array_values(array_filter(
            $cfdi->retenciones,
            fn (array $r): bool => $r['nivel'] === 'concept',
        ));
        $this->assertCount(1, $conceptRetenciones);
        $this->assertEqualsWithDelta(0.0125, $conceptRetenciones[0]['tasa_o_cuota'], 0.000001);
    }

    private function cfdiXmlString(int $majorVersion, string $total, string $moneda): string
    {
        $ns = $majorVersion === 3 ? 'http://www.sat.gob.mx/cfd/3' : 'http://www.sat.gob.mx/cfd/4';
        $ver = $majorVersion === 3 ? '3.3' : '4.0';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="'.$ns.'" Version="'.$ver.'" Total="'.$total.'" Moneda="'.$moneda.'"/>';
    }
}
