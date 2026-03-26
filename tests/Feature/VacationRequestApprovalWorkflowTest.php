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

class VacationRequestApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_step_approval_marks_vacation_approved(): void
    {
        $this->seed(RoleSeeder::class);

        $secretarioRole = Role::query()->where('slug', 'secretario_general')->firstOrFail();

        $policy = ApprovalPolicy::factory()
            ->forDocumentType(ApprovalPolicyDocumentType::VacationRequest)
            ->create();

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $secretarioRole->id,
            'combine_with_next' => CombineWithNext::And,
        ]);

        $requester = User::factory()->forRole('asesor')->create();
        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $vacation->refresh();
        $this->assertSame(VacationRequestStatus::ApprovalInProgress, $vacation->status);

        $secretario = User::factory()->forRole('secretario_general')->create();
        $approval = $vacation->approvals()->where('step_order', 1)->firstOrFail();

        $service->approve($approval, $secretario);

        $vacation->refresh();
        $this->assertSame(VacationRequestStatus::Approved, $vacation->status);
    }
}
