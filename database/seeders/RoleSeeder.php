<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Default organizational roles. Roles are fully editable from the UI;
     * this seeder only ensures a baseline set exists for fresh installs.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => 'super_admin', 'name' => 'Super administrador', 'description' => 'Acceso total al sistema.'],
            ['slug' => 'secretario_general', 'name' => 'Secretario general', 'description' => 'Coordina políticas, presupuestos y aprobaciones de alto nivel.'],
            ['slug' => 'contabilidad', 'name' => 'Contabilidad', 'description' => 'Revisa comprobaciones, registra pagos y liquida balances.'],
            ['slug' => 'coord_regional', 'name' => 'Coordinador regional', 'description' => 'Aprueba solicitudes a nivel regional.'],
            ['slug' => 'coord_estatal', 'name' => 'Coordinador estatal', 'description' => 'Aprueba solicitudes a nivel estatal.'],
            ['slug' => 'asesor', 'name' => 'Asesor', 'description' => 'Crea solicitudes propias.'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'description' => $role['description']],
            );
        }
    }
}
