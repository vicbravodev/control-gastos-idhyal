<?php

namespace App\Enums;

/**
 * Persisted settlement.status values (data-dictionary-stage2 § settlements).
 */
enum SettlementStatus: string
{
    case Calculated = 'calculated';

    case PendingUserReturn = 'pending_user_return';

    case PendingCompanyPayment = 'pending_company_payment';

    case Settled = 'settled';

    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Calculated => 'Calculado',
            self::PendingUserReturn => 'Pendiente: devolución',
            self::PendingCompanyPayment => 'Pendiente: pago complementario',
            self::Settled => 'Liquidado',
            self::Closed => 'Cerrado',
        };
    }
}
