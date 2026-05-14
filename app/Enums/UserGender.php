<?php

namespace App\Enums;

enum UserGender: string
{
    case Male = 'male';

    case Female = 'female';

    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Masculino',
            self::Female => 'Femenino',
            self::PreferNotToSay => 'Prefiero no especificar',
        };
    }

    public function greeting(): string
    {
        return match ($this) {
            self::Male => 'Bienvenido',
            self::Female => 'Bienvenida',
            self::PreferNotToSay => 'Bienvenido(a)',
        };
    }
}
