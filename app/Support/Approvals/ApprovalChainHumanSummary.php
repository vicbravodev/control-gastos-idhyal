<?php

namespace App\Support\Approvals;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalStepMode;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\ApprovalStepGrouper;

/**
 * Builds a human-readable Spanish summary of an approval chain, used in the
 * index page and as a server-rendered fallback for the form preview.
 */
class ApprovalChainHumanSummary
{
    public static function for(ApprovalPolicy $policy): string
    {
        $steps = $policy->steps->sortBy('step_order')->values();
        if ($steps->isEmpty()) {
            return '—';
        }

        $groups = ApprovalStepGrouper::stepOrderGroups($steps);
        $parts = [];

        $first = true;
        foreach ($groups as $orders) {
            $stepsInGroup = $steps
                ->filter(fn (ApprovalPolicyStep $s) => in_array($s->step_order, $orders, true))
                ->sortBy('step_order')
                ->values();

            $labels = $stepsInGroup
                ->map(fn (ApprovalPolicyStep $s) => self::approverLabel($s))
                ->all();

            $isAllOf = $stepsInGroup->count() > 1
                && $stepsInGroup->take($stepsInGroup->count() - 1)
                    ->contains(fn (ApprovalPolicyStep $s) => $s->step_mode === ApprovalStepMode::AllOf);

            if (count($labels) === 1) {
                $segment = $labels[0];
            } elseif ($isAllOf) {
                $segment = sprintf('todos: [%s]', implode(', ', $labels));
            } else {
                $segment = sprintf('cualquiera de [%s]', implode(', ', $labels));
            }

            $parts[] = $first
                ? 'Primero aprueba '.$segment
                : 'después aprueba '.$segment;
            $first = false;
        }

        return implode(', ', $parts).'.';
    }

    public static function approverLabel(ApprovalPolicyStep $step): string
    {
        return match ($step->approver_type) {
            ApprovalApproverType::Role => Role::query()->find($step->approver_id)?->name ?? 'Rol eliminado',
            ApprovalApproverType::Department => Department::query()->find($step->approver_id)?->name ?? 'Departamento eliminado',
            ApprovalApproverType::User => User::query()->find($step->approver_id)?->name ?? 'Usuario eliminado',
        };
    }

    public static function appliesToLabel(ApprovalPolicy $policy): string
    {
        if ($policy->applies_to_type === null) {
            return 'Todas las solicitudes (por defecto)';
        }

        return match ($policy->applies_to_type) {
            ApprovalApproverType::Role => sprintf('Rol: %s', Role::query()->find($policy->applies_to_id)?->name ?? '—'),
            ApprovalApproverType::Department => sprintf('Departamento: %s', Department::query()->find($policy->applies_to_id)?->name ?? '—'),
            ApprovalApproverType::User => sprintf('Usuario: %s', User::query()->find($policy->applies_to_id)?->name ?? '—'),
        };
    }
}
