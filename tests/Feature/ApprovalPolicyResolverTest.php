<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\ApprovalPolicyResolver;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalPolicyResolverTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalPolicyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->resolver = new ApprovalPolicyResolver;
    }

    public function test_throws_when_no_matching_policy(): void
    {
        $user = User::factory()->create();

        $this->expectException(NoActiveApprovalPolicyException::class);

        $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $user);
    }

    public function test_resolves_default_policy_when_requester_has_no_specific_match(): void
    {
        $conta = Role::query()->where('slug', 'contabilidad')->firstOrFail();
        $user = User::factory()->create(['role_id' => $conta->id]);

        $policy = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Default expense',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $conta->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $resolved = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $user);

        $this->assertTrue($resolved->is($policy));
        $this->assertCount(1, $resolved->steps);
    }

    public function test_specific_requester_role_wins_over_default(): void
    {
        $asesor = Role::query()->where('slug', 'asesor')->firstOrFail();
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();

        $defaultPolicy = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Default',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $defaultPolicy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $specificPolicy = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Asesor-specific',
            'version' => 1,
            'applies_to_type' => 'role', 'applies_to_id' => $asesor->id,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $specificPolicy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $user = User::factory()->create(['role_id' => $asesor->id]);

        $resolved = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $user);

        $this->assertTrue($resolved->is($specificPolicy));
    }

    public function test_higher_version_wins_among_same_tier(): void
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();

        $older = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::VacationRequest->value,
            'name' => 'V1',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $older->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $newer = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::VacationRequest->value,
            'name' => 'V2',
            'version' => 2,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $newer->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $user = User::factory()->create(['role_id' => $coord->id]);

        $resolved = $this->resolver->resolve(ApprovalPolicyDocumentType::VacationRequest, $user);

        $this->assertTrue($resolved->is($newer));
    }

    public function test_respects_effective_date_window(): void
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $user = User::factory()->create(['role_id' => $coord->id]);

        $expired = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Expired',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => '2020-12-31',
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $expired->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $active = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Active',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $active->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $resolved = $this->resolver->resolve(
            ApprovalPolicyDocumentType::ExpenseRequest,
            $user,
            CarbonImmutable::parse('2026-06-15'),
        );

        $this->assertTrue($resolved->is($active));
    }

    public function test_inactive_policies_are_ignored(): void
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $user = User::factory()->create(['role_id' => $coord->id]);

        ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Off',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => false,
        ]);

        $on = ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'On',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => true,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $on->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coord->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $resolved = $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $user);

        $this->assertTrue($resolved->is($on));
    }

    public function test_throws_when_policy_has_no_steps(): void
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $user = User::factory()->create(['role_id' => $coord->id]);

        ApprovalPolicy::query()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
            'name' => 'Empty',
            'version' => 1,
            'applies_to_type' => null, 'applies_to_id' => null,
            'is_active' => true,
        ]);

        $this->expectException(NoActiveApprovalPolicyException::class);

        $this->resolver->resolve(ApprovalPolicyDocumentType::ExpenseRequest, $user);
    }
}
