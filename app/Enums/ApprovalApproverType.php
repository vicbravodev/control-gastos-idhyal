<?php

namespace App\Enums;

enum ApprovalApproverType: string
{
    case Role = 'role';

    case Department = 'department';

    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Role => 'Rol',
            self::Department => 'Departamento',
            self::User => 'Usuario',
        };
    }
}
