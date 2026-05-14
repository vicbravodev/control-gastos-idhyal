<?php

namespace App\Services\ExpenseReports;

final class CfdiImpuestoCatalog
{
    private const LABELS = [
        '001' => 'ISR',
        '002' => 'IVA',
        '003' => 'IEPS',
    ];

    public static function label(string $impuesto): string
    {
        return self::LABELS[$impuesto] ?? $impuesto;
    }
}
