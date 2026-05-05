<?php

namespace Tests\Feature;

use App\Enums\DocumentEventType;
use App\Models\DocumentEvent;
use App\Models\User;
use App\Models\VacationEntitlementAdjustment;
use App\Models\VacationRule;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVacationAdjustmentsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function vacationRule(): VacationRule
    {
        return VacationRule::query()->create([
            'code' => 'BASE',
            'name' => 'Base',
            'min_years_service' => 0,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'max_days_per_request' => 10,
            'max_days_per_month' => 10,
            'max_days_per_quarter' => 10,
            'max_days_per_year' => 12,
            'blackout_dates' => [],
            'sort_order' => 1,
        ]);
    }

    public function test_non_super_admin_cannot_view_adjustments(): void
    {
        $employee = User::factory()->create();
        $intruder = User::factory()->forRole('asesor')->create();

        $this->actingAs($intruder)
            ->get(route('admin.users.vacation-adjustments.index', $employee))
            ->assertForbidden();
    }

    public function test_super_admin_can_register_a_positive_adjustment_returning_unused_days(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $employee = User::factory()->create(['hire_date' => now()->subYears(2)->toDateString()]);
        $this->vacationRule();

        $this->actingAs($admin)
            ->post(route('admin.users.vacation-adjustments.store', $employee), [
                'calendar_year' => (int) now()->year,
                'days' => 1,
                'reason' => 'Devolución por solicitar 8 y tomar 7',
            ])
            ->assertRedirect(route('admin.users.vacation-adjustments.index', $employee));

        $this->assertDatabaseHas('vacation_entitlement_adjustments', [
            'user_id' => $employee->id,
            'calendar_year' => (int) now()->year,
            'days' => 1,
            'reason' => 'Devolución por solicitar 8 y tomar 7',
            'granted_by_user_id' => $admin->id,
        ]);

        $this->assertTrue(
            DocumentEvent::query()
                ->where('subject_id', $employee->id)
                ->where('event_type', DocumentEventType::VacationEntitlementAdjusted)
                ->exists(),
        );
    }

    public function test_negative_adjustment_reduces_balance(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $employee = User::factory()->create(['hire_date' => now()->subYears(2)->toDateString()]);
        $this->vacationRule();

        $this->actingAs($admin)
            ->post(route('admin.users.vacation-adjustments.store', $employee), [
                'calendar_year' => (int) now()->year,
                'days' => -2,
                'reason' => 'Corrección por error administrativo',
            ])
            ->assertRedirect();

        $resolver = app(\App\Services\VacationRequests\VacationEntitlementBalanceResolver::class);
        $balance = $resolver->resolveForUser($employee);

        $this->assertSame(-2, $balance['days_adjustment']);
        $this->assertSame(10, $balance['days_remaining']);
    }

    public function test_zero_days_adjustment_is_rejected(): void
    {
        $admin = User::factory()->forRole('super_admin')->create();
        $employee = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.vacation-adjustments.index', $employee))
            ->post(route('admin.users.vacation-adjustments.store', $employee), [
                'calendar_year' => (int) now()->year,
                'days' => 0,
                'reason' => 'Sin sentido',
            ])
            ->assertSessionHasErrors('days');

        $this->assertSame(0, VacationEntitlementAdjustment::query()->count());
    }

    public function test_balance_resolver_includes_adjustment_in_remaining(): void
    {
        $employee = User::factory()->create(['hire_date' => now()->subYears(3)->toDateString()]);
        $this->vacationRule();

        VacationEntitlementAdjustment::query()->create([
            'user_id' => $employee->id,
            'calendar_year' => (int) now()->year,
            'days' => 3,
            'reason' => 'Premio',
            'granted_by_user_id' => User::factory()->forRole('super_admin')->create()->id,
        ]);

        $resolver = app(\App\Services\VacationRequests\VacationEntitlementBalanceResolver::class);
        $balance = $resolver->resolveForUser($employee);

        $this->assertSame(12, $balance['days_allocated']);
        $this->assertSame(3, $balance['days_adjustment']);
        $this->assertSame(15, $balance['days_remaining']);
    }
}
