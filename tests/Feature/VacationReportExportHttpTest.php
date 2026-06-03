<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VacationRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacationReportExportHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_view_report_page(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('admin.reports.vacations.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_report_page(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->forRole('super_admin')->create();

        $this->actingAs($admin)
            ->get(route('admin.reports.vacations.index'))
            ->assertOk();
    }

    public function test_export_returns_xlsx_file(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->forRole('super_admin')->create();
        $requester = User::factory()->forRole('asesor')->create([
            'hire_date' => '2020-01-15',
        ]);
        VacationRequest::factory()->create([
            'user_id' => $requester->id,
            'starts_on' => '2026-04-10',
            'ends_on' => '2026-04-14',
            'business_days_count' => 3,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.vacations.export', [
                'from' => '2026-01-01',
                'to' => '2026-12-31',
            ]));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type'),
        );
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_export_validates_required_dates(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->forRole('super_admin')->create();

        $this->actingAs($admin)
            ->from(route('admin.reports.vacations.index'))
            ->get(route('admin.reports.vacations.export'))
            ->assertSessionHasErrors(['from', 'to']);
    }
}
