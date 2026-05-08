<?php

namespace App\Enums;

enum ApprovalStepMode: string
{
    case Sequential = 'sequential';

    case AnyOf = 'any_of';

    case AllOf = 'all_of';

    public function label(): string
    {
        return match ($this) {
            self::Sequential => 'Pasa al siguiente paso (en orden)',
            self::AnyOf => 'Cualquiera de los siguientes también puede aprobar (basta con uno)',
            self::AllOf => 'Todos los siguientes también deben aprobar (todos firman)',
        };
    }
}
