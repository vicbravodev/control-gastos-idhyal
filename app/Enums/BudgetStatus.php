<?php

namespace App\Enums;

/**
 * budgets.status — controla si un presupuesto puede recibir nuevos compromisos.
 * Una vez cancelado el presupuesto queda en historial pero deja de ser elegible.
 */
enum BudgetStatus: string
{
    case Active = 'active';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Cancelled => 'slate',
        };
    }
}
