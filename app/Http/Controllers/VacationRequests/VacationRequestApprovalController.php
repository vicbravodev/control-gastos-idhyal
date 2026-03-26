<?php

namespace App\Http\Controllers\VacationRequests;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\VacationRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VacationRequests\ApproveVacationRequestApprovalRequest;
use App\Http\Requests\VacationRequests\RejectVacationRequestApprovalRequest;
use App\Models\VacationRequest;
use App\Models\VacationRequestApproval;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\VacationRequestApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VacationRequestApprovalController extends Controller
{
    public function pending(Request $request, VacationRequestApprovalService $approvalService): Response
    {
        $user = $request->user();
        $items = [];

        if ($user->role_id !== null) {
            $candidates = VacationRequestApproval::query()
                ->where('status', ApprovalInstanceStatus::Pending)
                ->where('role_id', $user->role_id)
                ->whereHas('vacationRequest', fn ($q) => $q->where('status', VacationRequestStatus::ApprovalInProgress))
                ->with(['vacationRequest.user', 'role'])
                ->orderByDesc('id')
                ->get();

            foreach ($candidates as $approval) {
                if (! $approvalService->isPendingStepActive($approval)) {
                    continue;
                }
                if (! $user->can('approve', $approval)) {
                    continue;
                }
                $vacation = $approval->vacationRequest;
                $items[] = [
                    'approval_id' => $approval->id,
                    'vacation_request_id' => $vacation->id,
                    'folio' => $vacation->folio,
                    'starts_on' => $vacation->starts_on?->toDateString(),
                    'ends_on' => $vacation->ends_on?->toDateString(),
                    'business_days_count' => $vacation->business_days_count,
                    'requester_name' => $vacation->user->name,
                    'step_order' => $approval->step_order,
                    'role_name' => $approval->role->name,
                ];
            }
        }

        return Inertia::render('vacation-requests/approvals/pending', [
            'items' => $items,
        ]);
    }

    public function approve(
        ApproveVacationRequestApprovalRequest $request,
        VacationRequest $vacation_request,
        VacationRequestApproval $approval,
        VacationRequestApprovalService $approvalService,
    ): RedirectResponse {
        try {
            $approvalService->approve($approval, $request->user());
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['approval' => $e->getMessage()]);
        }

        return redirect()
            ->route('vacation-requests.show', $vacation_request)
            ->with('status', __('Aprobación registrada.'));
    }

    public function reject(
        RejectVacationRequestApprovalRequest $request,
        VacationRequest $vacation_request,
        VacationRequestApproval $approval,
        VacationRequestApprovalService $approvalService,
    ): RedirectResponse {
        try {
            $approvalService->reject($approval, $request->user(), $request->string('note')->toString());
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['note' => $e->getMessage()]);
        }

        return redirect()
            ->route('vacation-requests.show', $vacation_request)
            ->with('status', __('Solicitud rechazada.'));
    }
}
