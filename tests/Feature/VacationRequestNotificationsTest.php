<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use App\Models\User;
use App\Models\VacationRequest;
use App\Notifications\VacationRequests\VacationRequestApprovalProgressNotification;
use App\Notifications\VacationRequests\VacationRequestFullyApprovedNotification;
use App\Notifications\VacationRequests\VacationRequestRejectedNotification;
use App\Notifications\VacationRequests\VacationRequestSubmittedNotification;
use App\Services\Approvals\VacationRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VacationRequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function seedVacationPolicyTwoSteps(): void
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

    public function test_start_workflow_notifies_first_group_approvers_only(): void
    {
        Notification::fake();
        $this->seedVacationPolicyTwoSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $coordApprover = User::factory()->forRole('coord_regional')->create();
        User::factory()->forRole('secretario_general')->create();

        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        app(VacationRequestApprovalService::class)->startWorkflow($vacation);

        Notification::assertSentTo($coordApprover, VacationRequestSubmittedNotification::class);
        Notification::assertNotSentTo($requester, VacationRequestSubmittedNotification::class);
    }

    public function test_partial_approval_notifies_requester_with_progress_and_final_step_sends_fully_approved(): void
    {
        Notification::fake();
        $this->seedVacationPolicyTwoSteps();

        $requester = User::factory()->forRole('asesor')->create();
        $coordApprover = User::factory()->forRole('coord_regional')->create();
        $secretarioApprover = User::factory()->forRole('secretario_general')->create();

        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        Notification::assertSentTo($coordApprover, VacationRequestSubmittedNotification::class);

        $step1 = $vacation->fresh()->approvals()->where('step_order', 1)->firstOrFail();
        $service->approve($step1, $coordApprover);

        Notification::assertSentTo($requester, VacationRequestApprovalProgressNotification::class);

        $step2 = $vacation->fresh()->approvals()->where('step_order', 2)->firstOrFail();
        $service->approve($step2, $secretarioApprover);

        Notification::assertSentTo($requester, VacationRequestFullyApprovedNotification::class);
    }

    public function test_reject_notifies_requester(): void
    {
        Notification::fake();
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
        $secretario = User::factory()->forRole('secretario_general')->create();

        $vacation = VacationRequest::factory()->submitted()->create([
            'user_id' => $requester->id,
        ]);

        $service = app(VacationRequestApprovalService::class);
        $service->startWorkflow($vacation);

        $approval = $vacation->fresh()->approvals()->where('step_order', 1)->firstOrFail();
        $service->reject($approval, $secretario, 'No hay cobertura');

        Notification::assertSentTo($requester, VacationRequestRejectedNotification::class);
    }
}
