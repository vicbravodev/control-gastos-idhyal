<?php

namespace App\Enums;

enum ExpenseRequestApprovalReason: string
{
    case Initial = 'initial';

    case OverCapExtension = 'over_cap_extension';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Inicial',
            self::OverCapExtension => 'Extensión por exceso de presupuesto',
        };
    }
}
