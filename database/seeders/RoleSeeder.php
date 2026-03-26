<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Organizational roles from functional spec §1.3.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => 'super_admin', 'name' => 'Super administrador'],
            ['slug' => 'secretario_general', 'name' => 'Secretario general'],
            ['slug' => 'contabilidad', 'name' => 'Contabilidad'],
            ['slug' => 'coord_regional', 'name' => 'Coordinador regional'],
            ['slug' => 'coord_estatal', 'name' => 'Coordinador estatal'],
            ['slug' => 'asesor', 'name' => 'Asesor'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name']]
            );
        }
    }
}
