<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\ApproveExpenseRequestApprovalRequest;
use App\Http\Requests\ExpenseRequests\RejectExpenseRequestApprovalRequest;
use App\Models\ExpenseRequest;
use App\Models\ExpenseRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
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

        if ($user->role_id !== null) {
            $candidates = ExpenseRequestApproval::query()
                ->where('status', ApprovalInstanceStatus::Pending)
                ->where('role_id', $user->role_id)
                ->whereHas('expenseRequest', fn ($q) => $q->where('status', ExpenseRequestStatus::ApprovalInProgress))
                ->with(['expenseRequest.user', 'expenseRequest.expenseConcept', 'role'])
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
                    'role_name' => $approval->role->name,
                ];
            }
        }

        return Inertia::render('expense-requests/approvals/pending', [
            'items' => $items,
        ]);
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
}
