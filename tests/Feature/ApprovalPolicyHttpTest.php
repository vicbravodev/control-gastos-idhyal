<?php

namespace Tests\Feature;

use App\Http\Controllers\ApprovalPolicies\ApprovalPolicyController;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApprovalPolicyHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Index ──────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('approval-policies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_view_index(): void
    {
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('approval-policies.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_index(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $policy = ApprovalPolicy::factory()->create();
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'approver_type' => 'role',
            'approver_id' => Role::query()->where('slug', 'contabilidad')->first()->id,
        ]);

        $this->actingAs($user)
            ->get(route('approval-policies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approval-policies/index')
                ->has('policies', 1));
    }

    public function test_super_admin_can_filter_policies_by_name_search(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        ApprovalPolicy::factory()->create(['name' => 'Zeta Policy Alpha']);
        ApprovalPolicy::factory()->create(['name' => 'Other Thing']);

        $this->actingAs($user)
            ->get(route('approval-policies.index', ['search' => 'Zeta']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approval-policies/index')
                ->has('policies', 1)
                ->where('policies.0.name', 'Zeta Policy Alpha')
                ->where('filters.search', 'Zeta'));
    }

    public function test_secretario_general_can_view_index(): void
    {
        $user = User::factory()->forRole('secretario_general')->create();

        $this->actingAs($user)
            ->get(route('approval-policies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approval-policies/index'));
    }

    // ── Create ─────────────────────────────────────────────────────────

    public function test_unauthorized_user_cannot_view_create_form(): void
    {
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('approval-policies.create'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_create_form(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->get(route('approval-policies.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approval-policies/create')
                ->has('roles')
                ->has('documentTypes'));
    }

    // ── Store ──────────────────────────────────────────────────────────

    public function test_super_admin_can_store_policy_with_steps(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $role = Role::query()->where('slug', 'contabilidad')->first();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => 'Test Policy',
                'version' => 1,
                'applies_to_type' => null, 'applies_to_id' => null,
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => $role->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertRedirect(action([ApprovalPolicyController::class, 'index']));

        $this->assertDatabaseHas('approval_policies', [
            'name' => 'Test Policy',
            'document_type' => 'expense_request',
        ]);

        $this->assertDatabaseHas('approval_policy_steps', [
            'approver_type' => 'role', 'approver_id' => $role->id,
            'step_order' => 1,
        ]);
    }

    public function test_store_creates_multiple_steps_in_order(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $coordRegional = Role::query()->where('slug', 'coord_regional')->first();
        $contabilidad = Role::query()->where('slug', 'contabilidad')->first();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => 'Multi-step Policy',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => $coordRegional->id, 'step_mode' => 'sequential'],
                    ['approver_type' => 'role',
                        'approver_id' => $contabilidad->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertRedirect();

        $policy = ApprovalPolicy::query()->where('name', 'Multi-step Policy')->first();
        $this->assertNotNull($policy);
        $this->assertCount(2, $policy->steps);
        $this->assertEquals(1, $policy->steps->sortBy('step_order')->first()->step_order);
        $this->assertEquals(2, $policy->steps->sortBy('step_order')->last()->step_order);
    }

    public function test_unauthorized_user_cannot_store_policy(): void
    {
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => 'Test',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => 1, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertForbidden();
    }

    // ── Store Validation ───────────────────────────────────────────────

    public function test_store_requires_name(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => '',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => Role::query()->first()->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_at_least_one_step(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => 'No Steps Policy',
                'version' => 1,
                'is_active' => true,
                'steps' => [],
            ])
            ->assertSessionHasErrors('steps');
    }

    public function test_store_rejects_invalid_document_type(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'invalid_type',
                'name' => 'Bad Type Policy',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => Role::query()->first()->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertSessionHasErrors('document_type');
    }

    public function test_store_validates_effective_to_after_from(): void
    {
        $user = User::factory()->forRole('super_admin')->create();

        $this->actingAs($user)
            ->post(route('approval-policies.store'), [
                'document_type' => 'expense_request',
                'name' => 'Date Test',
                'version' => 1,
                'is_active' => true,
                'effective_from' => '2026-12-31',
                'effective_to' => '2026-01-01',
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => Role::query()->first()->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertSessionHasErrors('effective_to');
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function test_super_admin_can_view_edit_form(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $policy = ApprovalPolicy::factory()->create();
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'approver_type' => 'role',
            'approver_id' => Role::query()->where('slug', 'contabilidad')->first()->id,
        ]);

        $this->actingAs($user)
            ->get(route('approval-policies.edit', $policy))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approval-policies/edit')
                ->has('policy')
                ->has('roles')
                ->has('documentTypes')
                ->has('can'));
    }

    public function test_unauthorized_user_cannot_view_edit_form(): void
    {
        $user = User::factory()->forRole('asesor')->create();
        $policy = ApprovalPolicy::factory()->create();

        $this->actingAs($user)
            ->get(route('approval-policies.edit', $policy))
            ->assertForbidden();
    }

    // ── Update ─────────────────────────────────────────────────────────

    public function test_super_admin_can_update_policy_and_sync_steps(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $policy = ApprovalPolicy::factory()->create(['name' => 'Original']);
        $oldRole = Role::query()->where('slug', 'contabilidad')->first();
        $newRole = Role::query()->where('slug', 'secretario_general')->first();

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'approver_type' => 'role',
            'approver_id' => $oldRole->id,
        ]);

        $this->actingAs($user)
            ->put(route('approval-policies.update', $policy), [
                'document_type' => 'expense_request',
                'name' => 'Updated',
                'version' => 2,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role', 'approver_id' => $newRole->id, 'step_mode' => 'any_of'],
                ],
            ])
            ->assertRedirect();

        $policy->refresh();
        $this->assertEquals('Updated', $policy->name);
        $this->assertEquals(2, $policy->version);
        $this->assertCount(1, $policy->steps);
        $this->assertEquals($newRole->id, $policy->steps->first()->approver_id);
        $this->assertEquals('any_of', $policy->steps->first()->step_mode->value);
    }

    public function test_secretario_general_can_update_policy(): void
    {
        $user = User::factory()->forRole('secretario_general')->create();
        $policy = ApprovalPolicy::factory()->create();
        $role = Role::query()->where('slug', 'contabilidad')->first();
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'approver_type' => 'role',
            'approver_id' => $role->id,
        ]);

        $this->actingAs($user)
            ->put(route('approval-policies.update', $policy), [
                'document_type' => 'expense_request',
                'name' => 'Sec Updated',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role', 'approver_id' => $role->id, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertRedirect();
    }

    public function test_unauthorized_user_cannot_update_policy(): void
    {
        $user = User::factory()->forRole('asesor')->create();
        $policy = ApprovalPolicy::factory()->create();

        $this->actingAs($user)
            ->put(route('approval-policies.update', $policy), [
                'document_type' => 'expense_request',
                'name' => 'Hack',
                'version' => 1,
                'is_active' => true,
                'steps' => [
                    ['approver_type' => 'role',
                        'approver_id' => 1, 'step_mode' => 'sequential'],
                ],
            ])
            ->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────────────────────

    public function test_super_admin_can_destroy_policy(): void
    {
        $user = User::factory()->forRole('super_admin')->create();
        $policy = ApprovalPolicy::factory()->create();
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'approver_type' => 'role',
            'approver_id' => Role::query()->where('slug', 'contabilidad')->first()->id,
        ]);

        $this->actingAs($user)
            ->delete(route('approval-policies.destroy', $policy))
            ->assertRedirect();

        $this->assertDatabaseMissing('approval_policies', ['id' => $policy->id]);
        $this->assertDatabaseMissing('approval_policy_steps', ['approval_policy_id' => $policy->id]);
    }

    public function test_secretario_general_cannot_destroy_policy(): void
    {
        $user = User::factory()->forRole('secretario_general')->create();
        $policy = ApprovalPolicy::factory()->create();

        $this->actingAs($user)
            ->delete(route('approval-policies.destroy', $policy))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_destroy_policy(): void
    {
        $user = User::factory()->forRole('asesor')->create();
        $policy = ApprovalPolicy::factory()->create();

        $this->actingAs($user)
            ->delete(route('approval-policies.destroy', $policy))
            ->assertForbidden();
    }
}
