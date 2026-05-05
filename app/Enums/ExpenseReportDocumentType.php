<?php

namespace App\Enums;

enum ExpenseReportDocumentType: string
{
    case Factura = 'factura';

    case Recibo = 'recibo';

    public function label(): string
    {
        return match ($this) {
            self::Factura => 'Factura (CFDI)',
            self::Recibo => 'Recibo (sin CFDI)',
        };
    }

    public function requiresXml(): bool
    {
        return $this === self::Factura;
    }
}
