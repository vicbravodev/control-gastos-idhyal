<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\VacationRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\Approvals\VacationRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacationRequestFinalApprovalReceiptHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRolesAndTwoStepVacationPolicy(): void
    {
        $this->seed(RoleSeeder::class);

        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $secretario = Role::query()->where('slug', 'secretario_general')->firstOrFail();

        $policy = ApprovalPolicy::factory()
            ->forDocumentType(ApprovalPolicyDocumentType::VacationRequest)
            ->create();

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $coord->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 2,
            'role_id' => $secretario->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
    }

    public function test_final_approval_receipt_pdf_downloadable_after_chain_complete(): void
    {
        $this->seedRolesAndTwoStepVacationPolicy();

        $requester = User::factory()->forRole('asesor')->create();
        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $secretarioUser = User::factory()->forRole('secretario_general')->create();

        $service->approve($vacation->fresh()->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);
        $service->approve($vacation->fresh()->approvals()->where('step_order', 2)->firstOrFail(), $secretarioUser);

        $vacation->refresh();
        $this->assertSame(VacationRequestStatus::Approved, $vacation->status);

        $response = $this->actingAs($requester)
            ->get(route('vacation-requests.receipts.final-approval', $vacation));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
        $this->assertNotEmpty($response->getContent());
    }

    public function test_oversight_user_can_download_final_approval_receipt_for_others_request(): void
    {
        $this->seedRolesAndTwoStepVacationPolicy();

        $requester = User::factory()->forRole('asesor')->create();
        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $secretarioUser = User::factory()->forRole('secretario_general')->create();

        $service->approve($vacation->fresh()->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);
        $service->approve($vacation->fresh()->approvals()->where('step_order', 2)->firstOrFail(), $secretarioUser);

        $oversight = User::factory()->forRole('coord_estatal')->create();

        $this->actingAs($oversight)
            ->get(route('vacation-requests.receipts.final-approval', $vacation->fresh()))
            ->assertOk();
    }

    public function test_final_approval_receipt_forbidden_while_approval_in_progress(): void
    {
        $this->seedRolesAndTwoStepVacationPolicy();

        $requester = User::factory()->forRole('asesor')->create();
        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $service->approve($vacation->fresh()->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);

        $vacation->refresh();
        $this->assertSame(VacationRequestStatus::ApprovalInProgress, $vacation->status);

        $this->actingAs($requester)
            ->get(route('vacation-requests.receipts.final-approval', $vacation))
            ->assertForbidden();
    }

    public function test_final_approval_receipt_forbidden_for_stranger(): void
    {
        $this->seed(RoleSeeder::class);

        $requester = User::factory()->forRole('asesor')->create();
        $stranger = User::factory()->forRole('asesor')->create();
        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
            'status' => VacationRequestStatus::Approved,
        ]);

        $this->actingAs($stranger)
            ->get(route('vacation-requests.receipts.final-approval', $vacation))
            ->assertForbidden();
    }
}
