<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\Approvals\ExpenseRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseRequestApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
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

    public function test_start_workflow_throws_when_no_policy_exists(): void
    {
        $this->seedRoles();
        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->expectException(NoActiveApprovalPolicyException::class);

        app(ExpenseRequestApprovalService::class)->startWorkflow($expense);
    }

    public function test_two_step_and_chain_reaches_pending_payment(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 99_000,
            'approved_amount_cents' => null,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::ApprovalInProgress, $expense->status);
        $this->assertCount(2, $expense->approvals);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $service->approve($step1, $coordUser);
        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::ApprovalInProgress, $expense->status);

        $service->approve($step2, $contaUser);
        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::PendingPayment, $expense->status);
        $this->assertSame(99_000, $expense->approved_amount_cents);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expense->id)
            ->where('event_type', DocumentEventType::ExpenseRequestChainApproved)
            ->where('actor_user_id', $contaUser->id)
            ->exists());
    }

    public function test_or_group_skips_sibling_pending_step(): void
    {
        $this->seedRoles();
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $sec = Role::query()->where('slug', 'secretario_general')->firstOrFail();
        $conta = Role::query()->where('slug', 'contabilidad')->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $coord->id,
            'combine_with_next' => CombineWithNext::Or,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 2,
            'role_id' => $sec->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 3,
            'role_id' => $conta->id,
            'combine_with_next' => CombineWithNext::And,
        ]);

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();
        $service->approve($step1, $coordUser);

        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();
        $step2->refresh();
        $this->assertSame('skipped', $step2->status->value);

        $contaUser = User::factory()->forRole('contabilidad')->create();
        $step3 = $expense->approvals()->where('step_order', 3)->firstOrFail();
        $service->approve($step3, $contaUser);

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::PendingPayment, $expense->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expense->id)
            ->where('event_type', DocumentEventType::ExpenseRequestChainApproved)
            ->where('actor_user_id', $contaUser->id)
            ->exists());
    }

    public function test_reject_creates_document_event_and_terminal_status(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();

        $service->reject($step1, $coordUser, 'Falta documentación');

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::Rejected, $expense->status);

        $this->assertDatabaseHas('document_events', [
            'subject_type' => 'expense_request',
            'subject_id' => $expense->id,
            'event_type' => DocumentEventType::Rejection->value,
            'actor_user_id' => $coordUser->id,
        ]);

        $event = DocumentEvent::query()->where('subject_id', $expense->id)->firstOrFail();
        $this->assertSame('Falta documentación', $event->note);
    }

    public function test_reject_requires_note(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();

        $this->expectException(InvalidApprovalStateException::class);

        $service->reject($step1, $coordUser, '   ');
    }

    public function test_cannot_approve_out_of_order_step(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $contaUser = User::factory()->forRole('contabilidad')->create();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $this->expectException(InvalidApprovalStateException::class);

        $service->approve($step2, $contaUser);
    }

    public function test_requester_cannot_approve_own_chain(): void
    {
        $this->seedRoles();
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $coord->id,
            'combine_with_next' => CombineWithNext::And,
        ]);

        $requester = User::factory()->forRole('coord_regional')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();

        $this->expectException(AuthorizationException::class);

        $service->approve($step1, $requester);
    }
}
