<?php

namespace App\Services\ExpenseReports;

use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
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

    private const TFD_URI = 'http://www.sat.gob.mx/TimbreFiscalDigital';

    /**
     * Backwards-compatible facade: parsea + valida reglas de negocio.
     *
     * @throws InvalidExpenseReportException
     */
    public function validate(UploadedFile $file, ?int $reportedAmountCents = null): CfdiComprobante
    {
        $cfdi = $this->parse($file);

        if (config('expense_reports.cfdi.validate_structure', true)) {
            $this->validateBusinessRules($cfdi, $reportedAmountCents);
        }

        return $cfdi;
    }

    /**
     * Parsea el XML y devuelve la metadata estructurada (sin reglas de negocio).
     *
     * @throws InvalidExpenseReportException
     */
    public function parse(UploadedFile $file): CfdiComprobante
    {
        $xml = $file->getContent();
        if ($xml === '' || $xml === false) {
            throw new InvalidExpenseReportException(__('No se pudo leer el archivo XML.'));
        }

        $element = $this->parseRootElement($xml);
        $this->assertSatComprobante($element);

        $version = $element->getAttribute('Version');
        if ($version === '') {
            throw new InvalidExpenseReportException(__('El XML no contiene el atributo Version del comprobante.'));
        }

        $totalAttr = $element->getAttribute('Total');
        if ($totalAttr === '') {
            throw new InvalidExpenseReportException(__('El XML no contiene el atributo Total del comprobante.'));
        }
        $totalCents = $this->parseTotalToCents($totalAttr);

        $moneda = strtoupper($element->getAttribute('Moneda')) ?: '';

        $emisor = $this->firstSatChild($element, 'Emisor');
        $receptor = $this->firstSatChild($element, 'Receptor');
        $tfd = $this->findTimbreFiscalDigital($element);
        $tfdUuid = $tfd !== null ? trim($tfd->getAttribute('UUID')) : '';

        return new CfdiComprobante(
            uuid: $tfdUuid !== '' ? strtoupper($tfdUuid) : null,
            totalCents: $totalCents,
            moneda: $moneda,
            version: $version,
            emisorRfc: $this->trimmedAttr($emisor, 'Rfc'),
            emisorNombre: $this->trimmedAttr($emisor, 'Nombre'),
            receptorRfc: $this->trimmedAttr($receptor, 'Rfc'),
            receptorNombre: $this->trimmedAttr($receptor, 'Nombre'),
            fecha: $this->parseFecha($element->getAttribute('Fecha')),
            serie: $this->trimmedAttr($element, 'Serie'),
            folio: $this->trimmedAttr($element, 'Folio'),
            formaPago: $this->trimmedAttr($element, 'FormaPago'),
            metodoPago: $this->trimmedAttr($element, 'MetodoPago'),
            usoCfdi: $this->trimmedAttr($receptor, 'UsoCFDI'),
            conceptos: $this->parseConceptos($element),
        );
    }

    /**
     * Reglas de negocio: total↔monto, moneda, RFC del receptor, rango de fecha.
     *
     * @throws InvalidExpenseReportException
     */
    public function validateBusinessRules(CfdiComprobante $cfdi, ?int $reportedAmountCents = null): void
    {
        if (config('expense_reports.cfdi.require_moneda_mxn', true) && $cfdi->moneda !== 'MXN') {
            throw new InvalidExpenseReportException(__('El comprobante debe estar en moneda MXN.'));
        }

        if (
            $reportedAmountCents !== null
            && config('expense_reports.cfdi.require_total_matches_reported', true)
        ) {
            $tolerance = (int) config('expense_reports.cfdi.total_match_tolerance_cents', 0);
            if (abs($cfdi->totalCents - $reportedAmountCents) > $tolerance) {
                throw new InvalidExpenseReportException(__(
                    'El Total del XML no coincide con el monto comprobado declarado.',
                ));
            }
        }

        $expectedReceiverRfc = config('expense_reports.cfdi.expected_receiver_rfc');
        if (is_string($expectedReceiverRfc) && $expectedReceiverRfc !== '') {
            $receiver = $cfdi->receptorRfc !== null ? strtoupper($cfdi->receptorRfc) : null;
            $expected = strtoupper($expectedReceiverRfc);
            if ($receiver !== $expected) {
                throw new InvalidExpenseReportException(__(
                    'El RFC del receptor (:got) no coincide con el RFC de la empresa (:expected). Verifica que la factura esté emitida a nombre de la organización.',
                    ['got' => $receiver ?? '—', 'expected' => $expected],
                ));
            }
        }

        if (config('expense_reports.cfdi.validate_fecha_range', true) && $cfdi->fecha !== null) {
            $maxAge = (int) config('expense_reports.cfdi.max_age_days', 365);
            $allowFuture = (int) config('expense_reports.cfdi.allow_future_days', 1);
            $now = CarbonImmutable::now();
            $oldestAllowed = $now->subDays($maxAge)->startOfDay();
            $latestAllowed = $now->addDays($allowFuture)->endOfDay();
            $fecha = CarbonImmutable::instance($cfdi->fecha);

            if ($fecha < $oldestAllowed) {
                throw new InvalidExpenseReportException(__(
                    'La factura tiene fecha :fecha y supera el máximo permitido de :max días de antigüedad.',
                    ['fecha' => $fecha->toDateString(), 'max' => $maxAge],
                ));
            }

            if ($fecha > $latestAllowed) {
                throw new InvalidExpenseReportException(__(
                    'La fecha del comprobante está en el futuro: :fecha.',
                    ['fecha' => $fecha->toDateString()],
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

    private function firstSatChild(DOMElement $parent, string $localName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement
                && ($child->localName ?? $child->tagName) === $localName
                && in_array($child->namespaceURI ?? '', self::SAT_COMPROBANTE_URIS, true)
            ) {
                return $child;
            }
        }

        return null;
    }

    private function findTimbreFiscalDigital(DOMElement $comprobante): ?DOMElement
    {
        $complemento = $this->firstSatChild($comprobante, 'Complemento');
        if ($complemento === null) {
            return null;
        }

        foreach ($complemento->childNodes as $child) {
            if ($child instanceof DOMElement
                && ($child->localName ?? $child->tagName) === 'TimbreFiscalDigital'
                && ($child->namespaceURI ?? '') === self::TFD_URI
            ) {
                return $child;
            }
        }

        return null;
    }

    private function trimmedAttr(?DOMElement $element, string $name): ?string
    {
        if ($element === null) {
            return null;
        }

        $value = trim($element->getAttribute($name));

        return $value === '' ? null : $value;
    }

    private function parseFecha(string $raw): ?DateTimeImmutable
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{descripcion: ?string, cantidad: ?float, unidad: ?string, valor_unitario: ?float, importe: ?float, clave_prod_serv: ?string}>
     */
    private function parseConceptos(DOMElement $comprobante): array
    {
        $wrapper = $this->firstSatChild($comprobante, 'Conceptos');
        if ($wrapper === null) {
            return [];
        }

        $conceptos = [];
        foreach ($wrapper->childNodes as $child) {
            if (! ($child instanceof DOMElement)) {
                continue;
            }
            if (($child->localName ?? $child->tagName) !== 'Concepto') {
                continue;
            }
            if (! in_array($child->namespaceURI ?? '', self::SAT_COMPROBANTE_URIS, true)) {
                continue;
            }

            $conceptos[] = [
                'descripcion' => $this->trimmedAttr($child, 'Descripcion'),
                'cantidad' => $this->floatAttr($child, 'Cantidad'),
                'unidad' => $this->trimmedAttr($child, 'Unidad'),
                'valor_unitario' => $this->floatAttr($child, 'ValorUnitario'),
                'importe' => $this->floatAttr($child, 'Importe'),
                'clave_prod_serv' => $this->trimmedAttr($child, 'ClaveProdServ'),
            ];
        }

        return $conceptos;
    }

    private function floatAttr(DOMElement $element, string $name): ?float
    {
        $value = trim($element->getAttribute($name));
        if ($value === '') {
            return null;
        }
        $normalized = str_replace([' ', ','], ['', ''], $value);
        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
