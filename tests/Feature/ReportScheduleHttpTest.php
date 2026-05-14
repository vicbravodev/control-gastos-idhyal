<?php

namespace Tests\Feature;

use App\Jobs\Reports\RunScheduledReport;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportScheduleHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    public function test_contabilidad_can_schedule_a_report(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'sched-builtin',
            'name' => 'Built-in',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => ['period' => 'mtd'],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reports.schedules.store'), [
                'template_id' => $template->id,
                'cadence' => 'daily',
                'time_of_day' => '08:30',
                'format' => 'pdf',
                'recipients' => ['ceo@example.com'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('report_schedules', [
            'owner_user_id' => $user->id,
            'template_id' => $template->id,
            'cadence' => 'daily',
            'format' => 'pdf',
        ]);
    }

    public function test_schedule_rejects_invalid_emails(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'sched-builtin2',
            'name' => 'Built-in',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => [],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reports.schedules.store'), [
                'template_id' => $template->id,
                'cadence' => 'daily',
                'time_of_day' => '08:30',
                'format' => 'pdf',
                'recipients' => ['not-an-email'],
            ])
            ->assertSessionHasErrors('recipients.0');
    }

    public function test_owner_can_delete_schedule(): void
    {
        $this->seedRoles();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'sched-builtin3',
            'name' => 'Built-in',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => [],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        $schedule = ReportSchedule::query()->create([
            'owner_user_id' => $user->id,
            'template_id' => $template->id,
            'cadence' => 'daily',
            'time_of_day' => '08:30:00',
            'format' => 'pdf',
            'recipients' => ['a@b.com'],
            'active' => true,
            'next_run_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->delete(route('reports.schedules.destroy', $schedule))
            ->assertRedirect();

        $this->assertDatabaseMissing('report_schedules', ['id' => $schedule->id]);
    }

    public function test_dispatch_due_command_queues_jobs_for_active_schedules(): void
    {
        $this->seedRoles();

        Queue::fake();

        $user = User::factory()->forRole('contabilidad')->create();
        $template = ReportTemplate::query()->create([
            'owner_user_id' => null,
            'slug' => 'sched-builtin4',
            'name' => 'Built-in',
            'icon' => 'bookmark',
            'view' => 'resumen',
            'filters' => ['period' => 'mtd'],
            'is_built_in' => true,
            'is_shared' => true,
        ]);

        // Due
        ReportSchedule::query()->create([
            'owner_user_id' => $user->id,
            'template_id' => $template->id,
            'cadence' => 'daily',
            'time_of_day' => '07:00:00',
            'format' => 'pdf',
            'recipients' => ['a@b.com'],
            'active' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        // Not yet due
        ReportSchedule::query()->create([
            'owner_user_id' => $user->id,
            'template_id' => $template->id,
            'cadence' => 'daily',
            'time_of_day' => '07:00:00',
            'format' => 'pdf',
            'recipients' => ['a@b.com'],
            'active' => true,
            'next_run_at' => now()->addHour(),
        ]);

        // Inactive
        ReportSchedule::query()->create([
            'owner_user_id' => $user->id,
            'template_id' => $template->id,
            'cadence' => 'daily',
            'time_of_day' => '07:00:00',
            'format' => 'pdf',
            'recipients' => ['a@b.com'],
            'active' => false,
            'next_run_at' => now()->subDay(),
        ]);

        $this->artisan('reports:dispatch-due-schedules')->assertSuccessful();

        Queue::assertPushed(RunScheduledReport::class, 1);
    }
}
