<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\ExpenseRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseRequestFinalApprovalReceiptHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function createExpensePolicyWithTwoAndSteps(): void
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
    }

    public function test_final_approval_receipt_pdf_downloadable_after_chain_complete(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 50_000,
            'approved_amount_cents' => null,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $service->approve($step1, $coordUser);
        $service->approve($step2, $contaUser);

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::PendingPayment, $expense->status);

        $response = $this->actingAs($requester)
            ->get(route('expense-requests.receipts.final-approval', $expense));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
        $this->assertNotEmpty($response->getContent());
    }

    public function test_accounting_can_download_final_approval_receipt_for_others_request(): void
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
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $service->approve($expense->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);
        $service->approve($expense->approvals()->where('step_order', 2)->firstOrFail(), $contaUser);

        $accounting = User::factory()->forRole('contabilidad')->create();

        $response = $this->actingAs($accounting)
            ->get(route('expense-requests.receipts.final-approval', $expense->fresh()));

        $response->assertOk();
    }

    public function test_final_approval_receipt_forbidden_while_approval_in_progress(): void
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
        $service->approve($expense->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::ApprovalInProgress, $expense->status);

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.final-approval', $expense))
            ->assertForbidden();
    }

    public function test_final_approval_receipt_forbidden_for_stranger(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $stranger = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'approved_amount_cents' => 10_000,
        ]);

        $this->actingAs($stranger)
            ->get(route('expense-requests.receipts.final-approval', $expense))
            ->assertForbidden();
    }
}
