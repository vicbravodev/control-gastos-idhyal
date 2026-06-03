<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestApprovalReason;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\DocumentEvent;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseRequestApprovalProgressNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestFullyApprovedNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestRejectedNotification;
use App\Notifications\ExpenseRequests\ExpenseRequestSubmittedNotification;
use App\Services\Approvals\ExpenseRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseRequestHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function activeExpenseConcept(): ExpenseConcept
    {
        return ExpenseConcept::factory()->create(['is_active' => true]);
    }

    public function test_super_admin_can_rebuild_orphaned_approval_chain_after_policy_change(): void
    {
        $this->seedRoles();
        Notification::fake();

        $oldPolicy = $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $this->assertSame(ExpenseRequestStatus::ApprovalInProgress, $expense->status);
        $this->assertSame(2, $expense->approvals()->count());

        // Simulate the admin changing the policy: delete old policy, create a new one with different approvers.
        $oldPolicy->steps()->delete();
        $oldPolicy->delete();
        $newSecGen = Role::query()->where('slug', 'secretario_general')->firstOrFail();
        $newPolicy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $newPolicy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $newSecGen->id,
            'step_mode' => ApprovalStepMode::Sequential,
        ]);

        // Confirm the chain points at the old (now-deleted) approvers and would be unworkable.
        $oldCoordRoleId = Role::query()->where('slug', 'coord_regional')->value('id');
        $this->assertTrue($expense->approvals()
            ->where('approver_id', $oldCoordRoleId)
            ->exists());

        // Rebuild as super admin
        $superAdmin = User::factory()->forRole('super_admin')->create();
        $this->actingAs($superAdmin)
            ->post(route('expense-requests.approvals.rebuild-workflow', $expense))
            ->assertRedirect(route('expense-requests.show', $expense));

        $expense->refresh();
        $rebuilt = $expense->approvals()->where('reason', ExpenseRequestApprovalReason::Initial)->get();
        $this->assertCount(1, $rebuilt);
        $this->assertSame($newSecGen->id, $rebuilt->first()->approver_id);
        $this->assertSame(ApprovalInstanceStatus::Pending, $rebuilt->first()->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expense->id)
            ->where('event_type', DocumentEventType::ExpenseRequestApprovalChainRebuilt->value)
            ->exists());
    }

    public function test_non_super_admin_cannot_rebuild_workflow(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $coord = User::factory()->forRole('coord_regional')->create();

        $this->actingAs($coord)
            ->post(route('expense-requests.approvals.rebuild-workflow', $expense))
            ->assertForbidden();
    }

    private function createExpensePolicyWithTwoAndSteps(): ApprovalPolicy
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $conta = Role::query()->where('slug', 'contabilidad')->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 2,
            'approver_type' => 'role',
            'approver_id' => $conta->id,
            'step_mode' => ApprovalStepMode::Sequential,
        ]);

        return $policy;
    }

    public function test_guests_cannot_view_expense_request_index(): void
    {
        $this->get(route('expense-requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_expense_request_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('expense-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/index')
                ->has('filters')
                ->has('available_statuses'));
    }

    public function test_expense_request_index_search_and_status_filter_query(): void
    {
        $user = User::factory()->create();
        ExpenseRequest::factory()->create([
            'user_id' => $user->id,
            'folio' => 'EXP-FILTER-AAA',
            'status' => ExpenseRequestStatus::Submitted,
        ]);
        ExpenseRequest::factory()->create([
            'user_id' => $user->id,
            'folio' => 'EXP-OTHER-BBB',
            'status' => ExpenseRequestStatus::Approved,
        ]);

        $this->actingAs($user)
            ->get(route('expense-requests.index', [
                'search' => 'FILTER-AAA',
                'status' => ExpenseRequestStatus::Submitted->value,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseRequests.data', 1)
                ->where('expenseRequests.data.0.folio', 'EXP-FILTER-AAA')
                ->where('filters.search', 'FILTER-AAA')
                ->where('filters.status', ExpenseRequestStatus::Submitted->value));
    }

    public function test_store_creates_request_starts_workflow_and_assigns_folio(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $response = $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Materiales de oficina',
                'delivery_method' => 'cash',
            ]);

        $response->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $this->assertNotNull($expense->folio);
        $this->assertStringStartsWith('EXP-'.now()->year.'-', $expense->folio);
        $this->assertSame(ExpenseRequestStatus::ApprovalInProgress, $expense->status);
        $this->assertCount(2, $expense->approvals);

        $this->assertDatabaseHas('document_events', [
            'subject_type' => $expense->getMorphClass(),
            'subject_id' => $expense->id,
            'event_type' => DocumentEventType::ExpenseRequestSubmitted->value,
            'actor_user_id' => $requester->id,
        ]);
    }

    public function test_regional_coordinator_must_pick_state_when_creating_request(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $region = Region::query()->create(['code' => 'RX', 'name' => 'Región X']);
        State::query()->create(['code' => 'X1', 'name' => 'Estado X1', 'region_id' => $region->id]);
        State::query()->create(['code' => 'X2', 'name' => 'Estado X2', 'region_id' => $region->id]);

        $coord = User::factory()->forRole('coord_regional')->create([
            'region_id' => $region->id,
            'state_id' => null,
        ]);
        $concept = $this->activeExpenseConcept();

        $this->actingAs($coord)
            ->from(route('expense-requests.create'))
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect(route('expense-requests.create'))
            ->assertSessionHasErrors('state_id');

        $this->assertDatabaseMissing('expense_requests', ['user_id' => $coord->id]);
    }

    public function test_regional_coordinator_stores_state_id_when_picked(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $region = Region::query()->create(['code' => 'RY', 'name' => 'Región Y']);
        $otherRegion = Region::query()->create(['code' => 'RZ-other', 'name' => 'Otra Región']);
        $stateA = State::query()->create(['code' => 'Y1', 'name' => 'Estado Y1', 'region_id' => $region->id]);
        $stateOutside = State::query()->create(['code' => 'Z9', 'name' => 'Estado Fuera', 'region_id' => $otherRegion->id]);

        $coord = User::factory()->forRole('coord_regional')->create([
            'region_id' => $region->id,
            'state_id' => null,
        ]);
        $concept = $this->activeExpenseConcept();

        // Rechaza estado fuera de su región
        $this->actingAs($coord)
            ->from(route('expense-requests.create'))
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
                'state_id' => $stateOutside->id,
            ])
            ->assertSessionHasErrors('state_id');

        // Acepta estado de su región
        $this->actingAs($coord)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
                'state_id' => $stateA->id,
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $coord->id)->firstOrFail();
        $this->assertSame($stateA->id, $expense->state_id);
    }

    public function test_create_page_exposes_available_states_only_to_regional_coordinator(): void
    {
        $this->seedRoles();

        $region = Region::query()->create(['code' => 'RZ', 'name' => 'Región Z']);
        State::query()->create(['code' => 'Z1', 'name' => 'Estado Z1', 'region_id' => $region->id]);
        State::query()->create(['code' => 'Z2', 'name' => 'Estado Z2', 'region_id' => $region->id]);

        $coordRegional = User::factory()->forRole('coord_regional')->create([
            'region_id' => $region->id,
            'state_id' => null,
        ]);
        $asesor = User::factory()->forRole('asesor')->create([
            'region_id' => $region->id,
            'state_id' => null,
        ]);

        $this->actingAs($coordRegional)
            ->get(route('expense-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/create')
                ->where('requiresStatePick', true)
                ->has('availableStates', 2));

        $this->actingAs($asesor)
            ->get(route('expense-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('requiresStatePick', true)
                ->has('availableStates', 2));
    }

    public function test_submission_receipt_pdf_is_downloadable_by_viewer(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $response = $this->actingAs($requester)
            ->get(route('expense-requests.receipts.submission', $expense));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
        $this->assertNotEmpty($response->getContent());
    }

    public function test_show_includes_approval_progress(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $this->actingAs($requester)
            ->get(route('expense-requests.show', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/show')
                ->where('expenseRequest.approval_progress.total_groups', 2)
                ->where('expenseRequest.approval_progress.remaining_groups', 2)
                ->where('expenseRequest.approval_progress.completed_groups', 0)
                ->has('expenseRequest.document_timeline', 1)
                ->where(
                    'expenseRequest.document_timeline.0.event_type',
                    DocumentEventType::ExpenseRequestSubmitted->value,
                ));
    }

    public function test_index_paginates_after_fifteen_items(): void
    {
        $user = User::factory()->create();
        ExpenseRequest::factory()->count(16)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('expense-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/index')
                ->where('expenseRequests.last_page', 2)
                ->where('expenseRequests.current_page', 1)
                ->has('expenseRequests.links'));
    }

    public function test_store_without_policy_returns_validation_error(): void
    {
        $this->seedRoles();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $response = $this->actingAs($requester)
            ->from(route('expense-requests.create'))
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $response->assertRedirect(route('expense-requests.create'));
        $response->assertSessionHasErrors('approval_policy');
        $this->assertSame(0, ExpenseRequest::query()->count());
    }

    public function test_owner_cannot_update_after_approval_starts(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Original',
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $this->actingAs($requester)
            ->patch(route('expense-requests.update', $expense), [
                'requested_amount_cents' => 99_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Changed',
                'delivery_method' => 'transfer',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_update_while_submitted(): void
    {
        $owner = User::factory()->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);
        $newConcept = ExpenseConcept::factory()->create(['is_active' => true]);

        $this->actingAs($owner)
            ->patch(route('expense-requests.update', $expense), [
                'requested_amount_cents' => 75_000,
                'expense_concept_id' => $newConcept->id,
                'concept_description' => 'Updated concept',
                'delivery_method' => 'transfer',
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $expense->refresh();
        $this->assertSame(75_000, $expense->requested_amount_cents);
        $this->assertSame('Updated concept', $expense->concept_description);
        $this->assertSame($newConcept->id, $expense->expense_concept_id);
    }

    public function test_reject_requires_note(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $coord = User::factory()->forRole('coord_regional')->create();

        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $approval = $expense->approvals()->where('step_order', 1)->firstOrFail();

        $this->actingAs($coord)
            ->post(route('expense-requests.approvals.reject', [
                'expenseRequest' => $expense,
                'approval' => $approval,
            ]), [])
            ->assertSessionHasErrors('note');
    }

    public function test_coordinator_can_reject_with_note(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $coord = User::factory()->forRole('coord_regional')->create();

        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $approval = $expense->approvals()->where('step_order', 1)->firstOrFail();

        $this->actingAs($coord)
            ->post(route('expense-requests.approvals.reject', [
                'expenseRequest' => $expense,
                'approval' => $approval,
            ]), [
                'note' => 'Falta documentación',
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::Rejected, $expense->status);
    }

    public function test_requester_cannot_approve_own_expense(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('coord_regional')->create();

        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $approval = ExpenseRequestApproval::factory()->create([
            'expense_request_id' => $expense->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $requester->role_id,
            'status' => ApprovalInstanceStatus::Pending,
        ]);

        $this->actingAs($requester)
            ->post(route('expense-requests.approvals.approve', [
                'expenseRequest' => $expense,
                'approval' => $approval,
            ]))
            ->assertForbidden();
    }

    public function test_pending_inbox_only_shows_active_step(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $coord = User::factory()->forRole('coord_regional')->create();
        $conta = User::factory()->forRole('contabilidad')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $this->actingAs($conta)
            ->get(route('expense-requests.approvals.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/approvals/pending')
                ->where('items', []));

        $this->actingAs($coord)
            ->get(route('expense-requests.approvals.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-requests/approvals/pending')
                ->has('items', 1));
    }

    public function test_is_pending_step_active_false_before_prior_step_completes(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();

        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $this->assertFalse(
            app(ExpenseRequestApprovalService::class)->isPendingStepActive($step2)
        );
    }

    public function test_store_notifies_users_in_first_approval_group(): void
    {
        Notification::fake();

        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $coordinator = User::factory()->forRole('coord_regional')->create();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Materiales',
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        Notification::assertSentTo($coordinator, ExpenseRequestSubmittedNotification::class);
    }

    public function test_first_approval_notifies_requester_with_progress(): void
    {
        Notification::fake();

        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $coordinator = User::factory()->forRole('coord_regional')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $approval = $expense->approvals()->where('step_order', 1)->firstOrFail();

        Notification::fake();

        $this->actingAs($coordinator)
            ->post(route('expense-requests.approvals.approve', [
                'expenseRequest' => $expense,
                'approval' => $approval,
            ]))
            ->assertRedirect();

        Notification::assertSentTo($requester, ExpenseRequestApprovalProgressNotification::class);
        Notification::assertNotSentTo($requester, ExpenseRequestFullyApprovedNotification::class);
    }

    public function test_final_approval_notifies_requester_fully_approved(): void
    {
        Notification::fake();

        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $coordinator = User::factory()->forRole('coord_regional')->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('expense-requests.approvals.approve', [
                'expenseRequest' => $expense,
                'approval' => $step1,
            ]))
            ->assertRedirect();

        Notification::fake();

        $this->actingAs($accounting)
            ->post(route('expense-requests.approvals.approve', [
                'expenseRequest' => $expense->fresh(),
                'approval' => $step2->fresh(),
            ]))
            ->assertRedirect();

        Notification::assertSentTo($requester, ExpenseRequestFullyApprovedNotification::class);
    }

    public function test_reject_notifies_requester(): void
    {
        Notification::fake();

        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $coordinator = User::factory()->forRole('coord_regional')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $approval = $expense->approvals()->where('step_order', 1)->firstOrFail();

        Notification::fake();

        $this->actingAs($coordinator)
            ->post(route('expense-requests.approvals.reject', [
                'expenseRequest' => $expense,
                'approval' => $approval,
            ]), [
                'note' => 'Documentación incompleta',
            ])
            ->assertRedirect();

        Notification::assertSentTo($requester, ExpenseRequestRejectedNotification::class);
    }
}
