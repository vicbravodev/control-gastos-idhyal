<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Tecnología',
            'Contabilidad',
            'Ventas',
            'Operaciones',
            'Recursos humanos',
            'Asesoría',
            'Coordinación',
            'Dirección',
        ]);

        return [
            'code' => Str::upper(Str::slug($name, '_')),
            'name' => $name,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
