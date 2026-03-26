<?php

namespace App\Services\ExpenseReports;

use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use DOMDocument;
use DOMElement;
use Illuminate\Http\UploadedFile;
use LibXMLError;

final class CfdiComprobanteValidator
{
    /**
     * Namespaces oficiales del elemento raíz Comprobante (SAT México).
     *
     * @var list<string>
     */
    private const SAT_COMPROBANTE_URIS = [
        'http://www.sat.gob.mx/cfd/3',
        'http://www.sat.gob.mx/cfd/4',
    ];

    /**
     * @throws InvalidExpenseReportException
     */
    public function validate(UploadedFile $file, ?int $reportedAmountCents = null): void
    {
        if (! config('expense_reports.cfdi.validate_structure', true)) {
            return;
        }

        $xml = $file->getContent();
        if ($xml === '' || $xml === false) {
            throw new InvalidExpenseReportException(__('No se pudo leer el archivo XML.'));
        }

        $element = $this->parseRootElement($xml);
        $this->assertSatComprobante($element);

        $total = $element->getAttribute('Total');
        if ($total === '') {
            throw new InvalidExpenseReportException(__('El XML no contiene el atributo Total del comprobante.'));
        }

        $version = $element->getAttribute('Version');
        if ($version === '') {
            throw new InvalidExpenseReportException(__('El XML no contiene el atributo Version del comprobante.'));
        }

        if (config('expense_reports.cfdi.require_moneda_mxn', true)) {
            $moneda = strtoupper($element->getAttribute('Moneda'));
            if ($moneda !== 'MXN') {
                throw new InvalidExpenseReportException(__('El comprobante debe estar en moneda MXN.'));
            }
        }

        $totalCents = $this->parseTotalToCents($total);

        if (
            $reportedAmountCents !== null
            && config('expense_reports.cfdi.require_total_matches_reported', true)
        ) {
            $tolerance = (int) config('expense_reports.cfdi.total_match_tolerance_cents', 0);
            if (abs($totalCents - $reportedAmountCents) > $tolerance) {
                throw new InvalidExpenseReportException(__(
                    'El Total del XML no coincide con el monto comprobado declarado.',
                ));
            }
        }
    }

    /**
     * @throws InvalidExpenseReportException
     */
    private function parseRootElement(string $xml): DOMElement
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument;
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false || $document->documentElement === null) {
            throw new InvalidExpenseReportException($this->formatLibXmlErrors($errors));
        }

        return $document->documentElement;
    }

    /**
     * @param  list<LibXMLError>  $errors
     */
    private function formatLibXmlErrors(array $errors): string
    {
        if ($errors === []) {
            return __('El archivo XML no es válido o está vacío.');
        }

        $first = $errors[0];

        return __('El archivo XML no es válido: :message', [
            'message' => trim($first->message),
        ]);
    }

    /**
     * @throws InvalidExpenseReportException
     */
    private function assertSatComprobante(DOMElement $element): void
    {
        $uri = $element->namespaceURI ?? '';
        $local = $element->localName ?? $element->tagName;

        if ($local !== 'Comprobante' || ! in_array($uri, self::SAT_COMPROBANTE_URIS, true)) {
            throw new InvalidExpenseReportException(__(
                'El XML no es un CFDI válido: se esperaba el elemento Comprobante del SAT (CFDI 3.3 o 4.0).',
            ));
        }
    }

    /**
     * @throws InvalidExpenseReportException
     */
    private function parseTotalToCents(string $total): int
    {
        $normalized = str_replace([' ', ','], ['', ''], trim($total));
        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new InvalidExpenseReportException(__('El atributo Total del comprobante no es numérico.'));
        }

        if (function_exists('bcmul')) {
            return (int) bcmul($normalized, '100', 0);
        }

        return (int) round(((float) $normalized) * 100.0);
    }
}
