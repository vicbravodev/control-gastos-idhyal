<?php

namespace App\Services\ExpenseReports;

final class CfdiComprobanteIngestionResult
{
    public function __construct(
        public readonly CfdiComprobante $cfdi,
        public readonly int $resolvedAmountCents,
    ) {}
}
