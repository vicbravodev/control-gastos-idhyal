<?php

namespace App\Enums;

/**
 * payments.payment_method (data-dictionary-stage2); extensible in DB.
 */
enum PaymentMethod: string
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
