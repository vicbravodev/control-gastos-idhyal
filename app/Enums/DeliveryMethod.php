<?php

namespace App\Enums;

/**
 * expense_requests.delivery_method (data-dictionary-stage2).
 */
enum DeliveryMethod: string
{
    case Cash = 'cash';

    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Transfer => 'Transferencia',
        };
    }
}
