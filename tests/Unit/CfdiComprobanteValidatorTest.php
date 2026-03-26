<?php

namespace Tests\Unit;

use App\Services\ExpenseReports\CfdiComprobanteValidator;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CfdiComprobanteValidatorTest extends TestCase
{
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

    public function test_skips_validation_when_disabled_in_config(): void
    {
        Config::set('expense_reports.cfdi.validate_structure', false);

        $file = UploadedFile::fake()->createWithContent('x.xml', '<root/>');

        $this->validator->validate($file, 100);

        $this->assertTrue(true);
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

    private function cfdiXmlString(int $majorVersion, string $total, string $moneda): string
    {
        $ns = $majorVersion === 3 ? 'http://www.sat.gob.mx/cfd/3' : 'http://www.sat.gob.mx/cfd/4';
        $ver = $majorVersion === 3 ? '3.3' : '4.0';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<cfdi:Comprobante xmlns:cfdi="'.$ns.'" Version="'.$ver.'" Total="'.$total.'" Moneda="'.$moneda.'"/>';
    }
}
