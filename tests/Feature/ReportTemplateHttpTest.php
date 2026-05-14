<?php

namespace Tests\Feature;

use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTemplateHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    public function test_contabilidad_can_save_a_template(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($user)
            ->post(route('reports.templates.store'), [
                'name' => 'Mi vista',
                'description' => 'Periodo l30 aprobadas',
                'icon' => 'bookmark',
                'view' => 'detalle',
                'filters' => ['period' => 'l30', 'status' => 'approved'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('report_templates', [
            'name' => 'Mi vista',
            'owner_user_id' => $user->id,
            'is_built_in' => false,
        ]);
    }

    public function test_owner_can_update_their_template(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Original',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => ['period' => 'ytd'],
        ]);

        $this->actingAs($user)
            ->patch(route('reports.templates.update', $template), [
                'name' => 'Renombrada',
            ])
            ->assertRedirect();

        $this->assertSame('Renombrada', $template->refresh()->name);
    }

    public function test_non_owner_cannot_update_template(): void
    {
        $this->seedRoles();

        $owner = User::factory()->forRole('contabilidad')->create();
        $other = User::factory()->forRole('contabilidad')->create();

        $template = ReportTemplate::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Privada',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => [],
        ]);

        $this->actingAs($other)
            ->patch(route('reports.templates.update', $template), ['name' => 'Robada'])
            ->assertForbidden();

        $this->assertSame('Privada', $template->refresh()->name);
    }

    public function test_owner_can_delete_template(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Eliminar',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => [],
        ]);

        $this->actingAs($user)
            ->delete(route('reports.templates.destroy', $template))
            ->assertRedirect();

        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }

    public function test_built_in_template_cannot_be_modified(): void
    {
        $this->seedRoles();

        $admin = User::factory()->forRole('super_admin')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'builtin-test',
            'name' => 'Original',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => [],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('reports.templates.update', $template), ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_asesor_cannot_save_templates(): void
    {
        $this->seedRoles();

        $asesor = User::factory()->forRole('asesor')->create();

        $this->actingAs($asesor)
            ->post(route('reports.templates.store'), [
                'name' => 'Bloqueada',
                'view' => 'resumen',
                'filters' => [],
            ])
            ->assertForbidden();
    }
}
