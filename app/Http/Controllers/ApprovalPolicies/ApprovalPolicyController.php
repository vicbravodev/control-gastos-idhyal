<?php

namespace App\Http\Controllers\ApprovalPolicies;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalPolicies\StoreApprovalPolicyRequest;
use App\Http\Requests\ApprovalPolicies\UpdateApprovalPolicyRequest;
use App\Models\ApprovalPolicy;
use App\Models\Department;
use App\Models\Role;
use App\Support\Approvals\ApprovalChainHumanSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalPolicyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApprovalPolicy::class);

        $policies = ApprovalPolicy::query()
            ->with(['steps' => fn ($q) => $q->orderBy('step_order'), 'appliesTo'])
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('document_type')
            ->orderByDesc('is_active')
            ->orderByDesc('version')
            ->get()
            ->map(fn (ApprovalPolicy $policy): array => $this->presentPolicy($policy));

        return Inertia::render('approval-policies/index', [
            'policies' => $policies,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ApprovalPolicy::class);

        return Inertia::render('approval-policies/create', [
            'roles' => $this->roleOptions(),
            'departments' => $this->departmentOptions(),
            'documentTypes' => $this->documentTypeOptions(),
            'stepModes' => $this->stepModeOptions(),
            'userSearchUrl' => route('admin.users.search'),
        ]);
    }

    public function store(StoreApprovalPolicyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $policy = ApprovalPolicy::query()->create([
                'document_type' => $validated['document_type'],
                'name' => $validated['name'],
                'version' => $validated['version'],
                'applies_to_type' => $validated['applies_to_type'] ?? null,
                'applies_to_id' => $validated['applies_to_id'] ?? null,
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['steps'] as $index => $step) {
                $policy->steps()->create([
                    'step_order' => $index + 1,
                    'approver_type' => $step['approver_type'],
                    'approver_id' => $step['approver_id'],
                    'step_mode' => $step['step_mode'],
                ]);
            }
        });

        return redirect()->action([self::class, 'index'])
            ->with('status', 'Política de aprobación creada.');
    }

    public function edit(ApprovalPolicy $approvalPolicy): Response
    {
        $this->authorize('update', $approvalPolicy);

        $approvalPolicy->load(['steps' => fn ($q) => $q->orderBy('step_order'), 'steps.approver', 'appliesTo']);

        return Inertia::render('approval-policies/edit', [
            'policy' => $this->presentPolicyForEdit($approvalPolicy),
            'roles' => $this->roleOptions(),
            'departments' => $this->departmentOptions(),
            'documentTypes' => $this->documentTypeOptions(),
            'stepModes' => $this->stepModeOptions(),
            'userSearchUrl' => route('admin.users.search'),
            'can' => [
                'delete' => request()->user()?->can('delete', $approvalPolicy) ?? false,
            ],
        ]);
    }

    public function update(UpdateApprovalPolicyRequest $request, ApprovalPolicy $approvalPolicy): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $approvalPolicy): void {
            $approvalPolicy->update([
                'document_type' => $validated['document_type'],
                'name' => $validated['name'],
                'version' => $validated['version'],
                'applies_to_type' => $validated['applies_to_type'] ?? null,
                'applies_to_id' => $validated['applies_to_id'] ?? null,
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $approvalPolicy->steps()->delete();

            foreach ($validated['steps'] as $index => $step) {
                $approvalPolicy->steps()->create([
                    'step_order' => $index + 1,
                    'approver_type' => $step['approver_type'],
                    'approver_id' => $step['approver_id'],
                    'step_mode' => $step['step_mode'],
                ]);
            }
        });

        return redirect()->action([self::class, 'index'])
            ->with('status', 'Política de aprobación actualizada.');
    }

    public function destroy(ApprovalPolicy $approvalPolicy): RedirectResponse
    {
        $this->authorize('delete', $approvalPolicy);

        $approvalPolicy->delete();

        return redirect()->action([self::class, 'index'])
            ->with('status', 'Política de aprobación eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPolicy(ApprovalPolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'document_type' => $policy->document_type->value,
            'document_type_label' => $this->documentTypeLabel($policy->document_type),
            'name' => $policy->name,
            'version' => $policy->version,
            'applies_to_label' => ApprovalChainHumanSummary::appliesToLabel($policy),
            'chain_summary' => ApprovalChainHumanSummary::for($policy),
            'effective_from' => $policy->effective_from?->toDateString(),
            'effective_to' => $policy->effective_to?->toDateString(),
            'is_active' => $policy->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPolicyForEdit(ApprovalPolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'document_type' => $policy->document_type->value,
            'name' => $policy->name,
            'version' => $policy->version,
            'applies_to_type' => $policy->applies_to_type?->value,
            'applies_to_id' => $policy->applies_to_id,
            'applies_to_label' => $policy->appliesTo?->name ?? null,
            'effective_from' => $policy->effective_from?->toDateString(),
            'effective_to' => $policy->effective_to?->toDateString(),
            'is_active' => $policy->is_active,
            'steps' => $policy->steps->map(fn ($step): array => [
                'approver_type' => $step->approver_type->value,
                'approver_id' => $step->approver_id,
                'approver_label' => $step->approver?->name ?? '—',
                'step_mode' => $step->step_mode->value,
            ])->all(),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => ['id' => $role->id, 'name' => $role->name])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function departmentOptions(): array
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $d): array => ['id' => $d->id, 'name' => $d->name])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function documentTypeOptions(): array
    {
        return array_map(
            fn (ApprovalPolicyDocumentType $type): array => [
                'value' => $type->value,
                'label' => $this->documentTypeLabel($type),
            ],
            ApprovalPolicyDocumentType::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function stepModeOptions(): array
    {
        return array_map(
            fn (ApprovalStepMode $mode): array => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ],
            ApprovalStepMode::cases(),
        );
    }

    private function documentTypeLabel(ApprovalPolicyDocumentType $type): string
    {
        return match ($type) {
            ApprovalPolicyDocumentType::ExpenseRequest => 'Solicitud de gasto',
            ApprovalPolicyDocumentType::VacationRequest => 'Solicitud de vacaciones',
        };
    }
}
