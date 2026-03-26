<?php

namespace Tests\Feature;

use App\Models\ExpenseRequest;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseRequestSubmittedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationInboxHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_notifications_inbox(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_notifications_preview_json(): void
    {
        $this->getJson(route('notifications.preview'))
            ->assertUnauthorized();
    }

    public function test_authenticated_user_sees_empty_inbox(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->has('notifications.data', 0));
    }

    public function test_inbox_lists_database_notifications_for_current_user(): void
    {
        $this->seed(RoleSeeder::class);

        $requester = User::factory()->forRole('asesor')->create(['name' => 'Solicitante Prueba']);
        $approver = User::factory()->forRole('coord_regional')->create();

        $expenseRequest = ExpenseRequest::factory()
            ->for($requester)
            ->create([
                'folio' => 'NOTIF-INBOX-1',
                'concept_description' => 'Concepto de prueba',
            ]);

        $approver->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $this->actingAs($approver)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.read_at', null)
                ->where('notifications.data.0.expense_request_id', $expenseRequest->id)
                ->where('notifications.data.0.vacation_request_id', null)
                ->has('notifications.data.0.body_lines'));
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $expenseRequest = ExpenseRequest::factory()->for($owner)->create();
        $owner->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $notificationId = $owner->notifications()->firstOrFail()->id;

        $this->actingAs($other)
            ->post(route('notifications.read', ['id' => $notificationId]))
            ->assertNotFound();
    }

    public function test_mark_notification_as_read_persists_read_at(): void
    {
        $user = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->for($user)->create();
        $user->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $notificationId = $user->notifications()->firstOrFail()->id;

        $this->actingAs($user)
            ->post(route('notifications.read', ['id' => $notificationId]))
            ->assertRedirect();

        $this->assertNotNull(
            $user->notifications()->whereKey($notificationId)->value('read_at'),
        );
    }

    public function test_mark_all_read_clears_unread_notifications(): void
    {
        $user = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->for($user)->create();

        $user->notify(new ExpenseRequestSubmittedNotification($expenseRequest));
        $user->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_shared_props_include_unread_notifications_count(): void
    {
        $user = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->for($user)->create();
        $user->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.unread_notifications_count', 1));
    }

    public function test_preview_json_returns_recent_notifications_for_current_user(): void
    {
        $this->seed(RoleSeeder::class);

        $requester = User::factory()->forRole('asesor')->create();
        $approver = User::factory()->forRole('coord_regional')->create();

        $expenseRequest = ExpenseRequest::factory()
            ->for($requester)
            ->create([
                'folio' => 'PREVIEW-1',
                'concept_description' => 'Prueba preview',
            ]);

        $approver->notify(new ExpenseRequestSubmittedNotification($expenseRequest));

        $this->actingAs($approver)
            ->getJson(route('notifications.preview'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.read_at', null)
            ->assertJsonPath('data.0.expense_request_id', $expenseRequest->id)
            ->assertJsonPath('data.0.vacation_request_id', null)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'read_at',
                        'created_at',
                        'title',
                        'body_lines',
                        'expense_request_id',
                        'vacation_request_id',
                    ],
                ],
            ]);
    }
}
