<?php

namespace Database\Seeders;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Demo approval policies for local development (requires {@see RoleSeeder} first).
 */
class ApprovalPolicySeeder extends Seeder
{
    public function run(): void
    {
        $coordRegional = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $contabilidad = Role::query()->where('slug', 'contabilidad')->firstOrFail();
        $secretario = Role::query()->where('slug', 'secretario_general')->firstOrFail();

        $expensePolicy = ApprovalPolicy::query()->updateOrCreate(
            [
                'document_type' => ApprovalPolicyDocumentType::ExpenseRequest->value,
                'name' => 'Gastos — coordinador regional y contabilidad',
                'applies_to_type' => null,
                'applies_to_id' => null,
                'version' => 1,
            ],
            [
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        ApprovalPolicyStep::query()->where('approval_policy_id', $expensePolicy->id)->delete();

        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $expensePolicy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $coordRegional->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);
        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $expensePolicy->id,
            'step_order' => 2,
            'approver_type' => 'role',
            'approver_id' => $contabilidad->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);

        $vacationPolicy = ApprovalPolicy::query()->updateOrCreate(
            [
                'document_type' => ApprovalPolicyDocumentType::VacationRequest->value,
                'name' => 'Vacaciones — secretario general',
                'applies_to_type' => null,
                'applies_to_id' => null,
                'version' => 1,
            ],
            [
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        ApprovalPolicyStep::query()->where('approval_policy_id', $vacationPolicy->id)->delete();

        ApprovalPolicyStep::query()->create([
            'approval_policy_id' => $vacationPolicy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $secretario->id,
            'step_mode' => ApprovalStepMode::Sequential->value,
        ]);
    }
}
