<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\ApproveExpenseReportRequest;
use App\Http\Requests\ExpenseRequests\RejectExpenseReportRequest;
use App\Http\Requests\ExpenseRequests\StoreExpenseReportDraftRequest;
use App\Http\Requests\ExpenseRequests\SubmitExpenseReportRequest;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Services\ExpenseReports\ApproveExpenseReport;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseReports\RejectExpenseReport;
use App\Services\ExpenseReports\SaveExpenseReportDraft;
use App\Services\ExpenseReports\SubmitExpenseReportForReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseReportController extends Controller
{
    public function pendingReview(Request $request): Response
    {
        $this->authorize('viewAny', ExpenseReport::class);

        $expenseRequests = ExpenseRequest::query()
            ->where('status', ExpenseRequestStatus::ExpenseReportInReview)
            ->with(['user', 'expenseConcept', 'expenseReport'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('folio', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(15)
            ->through(fn (ExpenseRequest $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'concept_label' => $r->conceptLabel(),
                'approved_amount_cents' => $r->approved_amount_cents,
                'created_at' => $r->created_at?->toIso8601String(),
                'user' => [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                ],
                'expense_report' => $r->expenseReport === null ? null : [
                    'id' => $r->expenseReport->id,
                    'reported_amount_cents' => $r->expenseReport->reported_amount_cents,
                    'submitted_at' => $r->expenseReport->submitted_at?->toIso8601String(),
                ],
            ]);

        return Inertia::render('expense-requests/expense-reports/pending-review', [
            'expenseRequests' => $expenseRequests,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function storeDraft(
        StoreExpenseReportDraftRequest $request,
        ExpenseRequest $expenseRequest,
        SaveExpenseReportDraft $saveDraft,
    ): RedirectResponse {
        try {
            $saveDraft->save(
                $expenseRequest,
                $request->user(),
                $request->integer('reported_amount_cents'),
                $request->file('pdf'),
                $request->file('xml'),
            );
        } catch (InvalidExpenseReportException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['expense_report' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Borrador de comprobación guardado.'));
    }

    public function submit(
        SubmitExpenseReportRequest $request,
        ExpenseRequest $expenseRequest,
        SubmitExpenseReportForReview $submitReport,
    ): RedirectResponse {
        try {
            $submitReport->submit(
                $expenseRequest,
                $request->user(),
                $request->integer('reported_amount_cents'),
                $request->file('pdf'),
                $request->file('xml'),
            );
        } catch (InvalidExpenseReportException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['expense_report' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Comprobación enviada a contabilidad.'));
    }

    public function approve(
        ApproveExpenseReportRequest $request,
        ExpenseRequest $expenseRequest,
        ApproveExpenseReport $approveReport,
    ): RedirectResponse {
        try {
            $approveReport->approve(
                $expenseRequest,
                $request->user(),
                $request->filled('note') ? $request->string('note')->toString() : null,
            );
        } catch (InvalidExpenseReportException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['expense_report' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Comprobación aprobada. Se generó el balance.'));
    }

    public function reject(
        RejectExpenseReportRequest $request,
        ExpenseRequest $expenseRequest,
        RejectExpenseReport $rejectReport,
    ): RedirectResponse {
        try {
            $rejectReport->reject(
                $expenseRequest,
                $request->user(),
                $request->string('note')->toString(),
            );
        } catch (InvalidExpenseReportException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['expense_report' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Comprobación rechazada. El solicitante fue notificado.'));
    }
}
