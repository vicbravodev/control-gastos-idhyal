<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\DocumentEventType;
use App\Enums\VacationRequestStatus;
use App\Models\DocumentEvent;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use App\Notifications\VacationRequests\VacationRequestExpiredNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpireStaleVacationRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config()->set('vacation_requests.pending_expiration_days', 7);
    }

    public function test_command_expires_stale_pending_requests(): void
    {
        Notification::fake();

        $stale = VacationRequest::factory()->create([
            'status' => VacationRequestStatus::Submitted,
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        $this->artisan('vacation-requests:expire-stale-pending')
            ->expectsOutputToContain('Solicitudes expiradas: 1')
            ->assertSuccessful();

        $stale->refresh();
        $this->assertSame(VacationRequestStatus::Expired, $stale->status);

        $this->assertTrue(
            DocumentEvent::query()
                ->where('subject_id', $stale->id)
                ->where('event_type', DocumentEventType::VacationRequestExpired)
                ->exists(),
        );

        Notification::assertSentTo($stale->user, VacationRequestExpiredNotification::class);
    }

    public function test_command_does_not_expire_recent_pending_requests(): void
    {
        Notification::fake();

        $fresh = VacationRequest::factory()->create([
            'status' => VacationRequestStatus::Submitted,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $this->artisan('vacation-requests:expire-stale-pending')->assertSuccessful();

        $fresh->refresh();
        $this->assertSame(VacationRequestStatus::Submitted, $fresh->status);

        Notification::assertNothingSent();
    }

    public function test_command_skips_pending_approvals_on_expired_request(): void
    {
        Notification::fake();

        $request = VacationRequest::factory()->create([
            'status' => VacationRequestStatus::ApprovalInProgress,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
        $approval = VacationRequestApproval::factory()->create([
            'vacation_request_id' => $request->id,
            'status' => ApprovalInstanceStatus::Pending,
        ]);

        $this->artisan('vacation-requests:expire-stale-pending')->assertSuccessful();

        $approval->refresh();
        $this->assertSame(ApprovalInstanceStatus::Skipped, $approval->status);
    }

    public function test_expired_status_does_not_consume_balance(): void
    {
        $request = VacationRequest::factory()->create([
            'status' => VacationRequestStatus::Expired,
            'starts_on' => now()->startOfYear()->addMonth(),
            'ends_on' => now()->startOfYear()->addMonth()->addDays(4),
            'business_days_count' => 5,
        ]);

        $resolver = app(\App\Services\VacationRequests\VacationEntitlementBalanceResolver::class);

        $consumed = $resolver->consumedDaysForUserInYear(
            $request->user,
            (int) now()->year,
        );

        $this->assertSame(0, $consumed);
    }
}
