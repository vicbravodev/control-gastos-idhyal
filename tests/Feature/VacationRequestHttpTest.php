<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\VacationRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationRule;
use App\Services\Approvals\VacationRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VacationRequestHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function createVacationPolicyWithTwoAndSteps(): ApprovalPolicy
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $conta = Role::query()->where('slug', 'contabilidad')->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::VacationRequest,
        ]);

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $coord->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 2,
            'role_id' => $conta->id,
            'combine_with_next' => CombineWithNext::And,
        ]);

        return $policy;
    }

    private function seedVacationRule(): VacationRule
    {
        return VacationRule::factory()->create([
            'code' => 'TEST_VAC_RULE',
            'min_years_service' => 1,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'max_days_per_request' => 10,
            'sort_order' => 1,
        ]);
    }

    public function test_guests_cannot_view_vacation_request_index(): void
    {
        $this->get(route('vacation-requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_vacation_request_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('vacation-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-requests/index')
                ->has('vacationBalance')
                ->has('filters')
                ->has('available_statuses'));
    }

    public function test_store_creates_request_starts_workflow_and_assigns_folio(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();

        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $response = $this->actingAs($requester)
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $response->assertRedirect();

        $vacation = VacationRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $this->assertNotNull($vacation->folio);
        $this->assertStringStartsWith('VAC-'.now()->year.'-', $vacation->folio);
        $this->assertSame(VacationRequestStatus::ApprovalInProgress, $vacation->status);
        $this->assertCount(2, $vacation->approvals);
        $this->assertSame(5, $vacation->business_days_count);
    }

    public function test_store_without_policy_returns_validation_error(): void
    {
        $this->seedRoles();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $response = $this->actingAs($requester)
            ->from(route('vacation-requests.create'))
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $response->assertRedirect(route('vacation-requests.create'));
        $response->assertSessionHasErrors('approval_policy');
        $this->assertSame(0, VacationRequest::query()->count());
    }

    public function test_store_rejects_range_with_no_weekdays(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $this->actingAs($requester)
            ->from(route('vacation-requests.create'))
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-21',
                'ends_on' => '2026-03-22',
            ])
            ->assertSessionHasErrors('ends_on');

        $this->assertSame(0, VacationRequest::query()->count());
    }

    public function test_show_includes_approval_progress(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $this->actingAs($requester)
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $vacation = VacationRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $this->actingAs($requester)
            ->get(route('vacation-requests.show', $vacation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-requests/show')
                ->where('vacationRequest.approval_progress.total_groups', 2)
                ->where('vacationRequest.approval_progress.remaining_groups', 2)
                ->where('vacationRequest.approval_progress.completed_groups', 0));
    }

    public function test_stranger_cannot_view_others_vacation_request(): void
    {
        $this->seedRoles();
        $owner = User::factory()->forRole('asesor')->create();
        $other = User::factory()->forRole('asesor')->create();

        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->get(route('vacation-requests.show', $vacation))
            ->assertForbidden();
    }

    public function test_pending_inbox_only_shows_active_step(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();
        $coord = User::factory()->forRole('coord_regional')->create();
        $conta = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($requester)
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $this->actingAs($conta)
            ->get(route('vacation-requests.approvals.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-requests/approvals/pending')
                ->where('items', []));

        $this->actingAs($coord)
            ->get(route('vacation-requests.approvals.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-requests/approvals/pending')
                ->has('items', 1));
    }

    public function test_requester_cannot_approve_own_vacation_step(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('coord_regional')->create();

        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $approval = $vacation->approvals()->where('step_order', 1)->firstOrFail();

        $this->actingAs($requester)
            ->post(route('vacation-requests.approvals.approve', [
                'vacation_request' => $vacation,
                'approval' => $approval,
            ]))
            ->assertForbidden();
    }

    public function test_is_pending_step_active_false_before_prior_step_completes(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $this->actingAs($requester)
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $vacation = VacationRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $step2 = $vacation->approvals()->where('step_order', 2)->firstOrFail();

        $this->assertFalse(
            app(VacationRequestApprovalService::class)->isPendingStepActive($step2)
        );
    }

    public function test_coordinator_can_approve_first_step_via_http(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();
        $coord = User::factory()->forRole('coord_regional')->create();

        $this->actingAs($requester)
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ]);

        $vacation = VacationRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $approval = $vacation->approvals()->where('step_order', 1)->firstOrFail();

        $this->actingAs($coord)
            ->post(route('vacation-requests.approvals.approve', [
                'vacation_request' => $vacation,
                'approval' => $approval,
            ]))
            ->assertRedirect(route('vacation-requests.show', $vacation));

        $vacation->refresh();
        $this->assertSame(VacationRequestStatus::ApprovalInProgress, $vacation->status);
        $this->assertSame(ApprovalInstanceStatus::Approved, $approval->fresh()->status);
    }

    public function test_create_includes_vacation_balance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('vacation-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-requests/create')
                ->has('vacationBalance'));
    }

    public function test_store_rejects_without_hire_date(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->create(['hire_date' => null]);

        $this->actingAs($requester)
            ->from(route('vacation-requests.create'))
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ])
            ->assertSessionHasErrors('hire_date');

        $this->assertSame(0, VacationRequest::query()->count());
    }

    public function test_store_rejects_when_insufficient_balance(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        $this->seedVacationRule();
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
            'starts_on' => '2026-03-02',
            'ends_on' => '2026-03-13',
            'business_days_count' => 10,
        ]);

        $this->actingAs($requester)
            ->from(route('vacation-requests.create'))
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-23',
                'ends_on' => '2026-03-27',
            ])
            ->assertSessionHasErrors('ends_on');

        $this->assertSame(1, VacationRequest::query()->count());
    }

    public function test_store_rejects_when_exceeds_max_days_per_request(): void
    {
        $this->seedRoles();
        $this->createVacationPolicyWithTwoAndSteps();
        VacationRule::factory()->create([
            'code' => 'MAX3',
            'min_years_service' => 1,
            'max_years_service' => null,
            'days_granted_per_year' => 12,
            'max_days_per_request' => 3,
            'sort_order' => 1,
        ]);
        $requester = User::factory()->forRole('asesor')->withHireDate()->create();

        $this->actingAs($requester)
            ->from(route('vacation-requests.create'))
            ->post(route('vacation-requests.store'), [
                'starts_on' => '2026-03-02',
                'ends_on' => '2026-03-05',
            ])
            ->assertSessionHasErrors('ends_on');

        $this->assertSame(0, VacationRequest::query()->count());
    }
}
