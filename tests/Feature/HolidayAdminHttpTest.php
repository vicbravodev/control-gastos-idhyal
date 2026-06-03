<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayAdminHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_access_holidays_index(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('admin.holidays.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_update_and_delete_holidays(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->forRole('super_admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.holidays.store'), [
                'date' => '2026-09-16',
                'name' => 'Día de la Independencia',
                'description' => 'Festivo oficial',
            ])
            ->assertRedirect(route('admin.holidays.index'));

        $holiday = Holiday::query()->where('date', '2026-09-16')->firstOrFail();
        $this->assertSame('Día de la Independencia', $holiday->name);

        $this->actingAs($admin)
            ->put(route('admin.holidays.update', $holiday), [
                'date' => '2026-09-16',
                'name' => 'Independencia (corregido)',
                'description' => null,
            ])
            ->assertRedirect(route('admin.holidays.index'));

        $this->assertSame('Independencia (corregido)', $holiday->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('admin.holidays.destroy', $holiday))
            ->assertRedirect(route('admin.holidays.index'));

        $this->assertNull(Holiday::query()->find($holiday->id));
    }

    public function test_holiday_date_must_be_unique(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->forRole('super_admin')->create();
        Holiday::query()->create([
            'date' => '2026-12-25',
            'name' => 'Navidad',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.holidays.create'))
            ->post(route('admin.holidays.store'), [
                'date' => '2026-12-25',
                'name' => 'Otro festivo',
            ])
            ->assertSessionHasErrors('date');
    }
}
