<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\DocumentEvent;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseRequestCancellationHttpTest extends TestCase
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

    public function test_owner_can_cancel_during_approval_with_note(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Viaje',
                'delivery_method' => 'cash',
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $pendingApproval = $expense->approvals()->where('status', ApprovalInstanceStatus::Pending)->firstOrFail();

        $this->actingAs($requester)
            ->post(route('expense-requests.cancel', $expense), [
                'note' => 'Ya no requiero el gasto',
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::Cancelled, $expense->status);
        $pendingApproval->refresh();
        $this->assertSame(ApprovalInstanceStatus::Skipped, $pendingApproval->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_type', $expense->getMorphClass())
            ->where('subject_id', $expense->id)
            ->where('event_type', DocumentEventType::Cancellation)
            ->where('actor_user_id', $requester->id)
            ->where('note', 'Ya no requiero el gasto')
            ->exists());
    }

    public function test_owner_can_cancel_when_submitted_without_approvals(): void
    {
        $this->seedRoles();
        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->actingAs($requester)
            ->post(route('expense-requests.cancel', $expense), [
                'note' => 'Motivo de la baja',
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::Cancelled, $expense->status);
    }

    public function test_cancel_requires_note(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 10_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $this->actingAs($requester)
            ->post(route('expense-requests.cancel', $expense), [])
            ->assertSessionHasErrors('note');
    }

    public function test_stranger_cannot_cancel(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $stranger = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 10_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
            ]);

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();

        $this->actingAs($stranger)
            ->post(route('expense-requests.cancel', $expense), [
                'note' => 'Intento indebido',
            ])
            ->assertForbidden();
    }

    public function test_owner_cannot_cancel_when_pending_payment(): void
    {
        $requester = User::factory()->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 40_000,
        ]);

        $this->actingAs($requester)
            ->post(route('expense-requests.cancel', $expense), [
                'note' => 'Demasiado tarde',
            ])
            ->assertForbidden();
    }
}
