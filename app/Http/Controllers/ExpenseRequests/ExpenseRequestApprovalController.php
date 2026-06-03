<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ApprovalApproverType;
use App\Enums\ApprovalInstanceStatus;
use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\ApproveExpenseRequestApprovalRequest;
use App\Http\Requests\ExpenseRequests\RejectExpenseRequestApprovalRequest;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\Approvals\ExpenseRequestApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseRequestApprovalController extends Controller
{
    public function pending(Request $request, ExpenseRequestApprovalService $approvalService): Response
    {
        $user = $request->user();
        $items = [];

        $candidates = ExpenseRequestApproval::query()
            ->where('status', ApprovalInstanceStatus::Pending)
            ->where(function ($query) use ($user): void {
                if ($user->role_id !== null) {
                    $query->orWhere(function ($q) use ($user): void {
                        $q->where('approver_type', ApprovalApproverType::Role->value)
                            ->where('approver_id', $user->role_id);
                    });
                }
                if ($user->department_id !== null) {
                    $query->orWhere(function ($q) use ($user): void {
                        $q->where('approver_type', ApprovalApproverType::Department->value)
                            ->where('approver_id', $user->department_id);
                    });
                }
                $query->orWhere(function ($q) use ($user): void {
                    $q->where('approver_type', ApprovalApproverType::User->value)
                        ->where('approver_id', $user->id);
                });
            })
            ->whereHas('expenseRequest', fn ($q) => $q->whereIn('status', [
                ExpenseRequestStatus::ApprovalInProgress,
                ExpenseRequestStatus::AwaitingExpenseReport,
                ExpenseRequestStatus::ExpenseReportInReview,
            ]))
            ->with(['expenseRequest.user', 'expenseRequest.expenseConcept', 'approver'])
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $approval) {
            if (! $approvalService->isPendingStepActive($approval)) {
                continue;
            }
            if (! $user->can('approve', $approval)) {
                continue;
            }
            $expense = $approval->expenseRequest;
            $items[] = [
                'approval_id' => $approval->id,
                'expense_request_id' => $expense->id,
                'folio' => $expense->folio,
                'concept_label' => $expense->conceptLabel(),
                'requested_amount_cents' => $expense->requested_amount_cents,
                'requester_name' => $expense->user->name,
                'step_order' => $approval->step_order,
                'approver_label' => $this->approverLabel($approval),
            ];
        }

        return Inertia::render('expense-requests/approvals/pending', [
            'items' => $items,
        ]);
    }

    private function approverLabel(ExpenseRequestApproval $approval): string
    {
        $entity = $approval->approver;
        $type = $approval->approver_type->label();
        $name = $entity?->name ?? $entity?->display_name ?? '—';

        return sprintf('%s: %s', $type, $name);
    }

    public function approve(
        ApproveExpenseRequestApprovalRequest $request,
        ExpenseRequest $expenseRequest,
        ExpenseRequestApproval $approval,
        ExpenseRequestApprovalService $approvalService,
    ): RedirectResponse {
        try {
            $approvalService->approve($approval, $request->user());
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['approval' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Aprobación registrada.'));
    }

    public function reject(
        RejectExpenseRequestApprovalRequest $request,
        ExpenseRequest $expenseRequest,
        ExpenseRequestApproval $approval,
        ExpenseRequestApprovalService $approvalService,
    ): RedirectResponse {
        try {
            $approvalService->reject($approval, $request->user(), $request->string('note')->toString());
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['note' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Solicitud rechazada.'));
    }

    public function rebuildWorkflow(
        Request $request,
        ExpenseRequest $expenseRequest,
        ExpenseRequestApprovalService $approvalService,
    ): RedirectResponse {
        $this->authorize('rebuildWorkflow', $expenseRequest);

        try {
            $approvalService->rebuildWorkflow($expenseRequest, $request->user());
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['rebuild_workflow' => $e->getMessage()]);
        } catch (NoActiveApprovalPolicyException) {
            return redirect()
                ->back()
                ->withErrors(['rebuild_workflow' => __('No hay una política activa para reasignar la cadena.')]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Cadena de aprobación rearmada con la política vigente.'));
    }
}
