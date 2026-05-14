<?php

namespace Tests\Unit\Enums;

use App\Enums\UserGender;
use PHPUnit\Framework\TestCase;

class UserGenderTest extends TestCase
{
    public function test_greeting_for_male_is_bienvenido(): void
    {
        $this->assertSame('Bienvenido', UserGender::Male->greeting());
    }

    public function test_greeting_for_female_is_bienvenida(): void
    {
        $this->assertSame('Bienvenida', UserGender::Female->greeting());
    }

    public function test_greeting_for_prefer_not_to_say_is_neutral(): void
    {
        $this->assertSame('Bienvenido(a)', UserGender::PreferNotToSay->greeting());
    }

    public function test_enum_values_match_database_strings(): void
    {
        $this->assertSame('male', UserGender::Male->value);
        $this->assertSame('female', UserGender::Female->value);
        $this->assertSame('prefer_not_to_say', UserGender::PreferNotToSay->value);
    }

    public function test_labels_are_in_spanish(): void
    {
        $this->assertSame('Masculino', UserGender::Male->label());
        $this->assertSame('Femenino', UserGender::Female->label());
        $this->assertSame('Prefiero no especificar', UserGender::PreferNotToSay->label());
    }
}
