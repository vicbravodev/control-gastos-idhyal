<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\ApproveExpenseReportRequest;
use App\Http\Requests\ExpenseRequests\RejectExpenseReportRequest;
use App\Http\Requests\ExpenseRequests\SubmitExpenseReportRequest;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Services\ExpenseReports\ApproveExpenseReport;
use App\Services\ExpenseReports\Exceptions\InvalidExpenseReportException;
use App\Services\ExpenseReports\RejectExpenseReport;
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

        $reports = ExpenseReport::query()
            ->where('status', ExpenseReportStatus::AccountingReview)
            ->whereHas('expenseRequest', function ($q): void {
                $q->where('status', ExpenseRequestStatus::ExpenseReportInReview);
            })
            ->with(['expenseRequest.user', 'expenseRequest.expenseConcept'])
            ->when($request->query('search'), function ($q, $search): void {
                $q->whereHas('expenseRequest', function ($sub) use ($search): void {
                    $sub->where('folio', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('submitted_at')
            ->paginate(15)
            ->through(fn (ExpenseReport $r) => [
                'id' => $r->id,
                'reported_amount_cents' => $r->reported_amount_cents,
                'submitted_at' => $r->submitted_at?->toIso8601String(),
                'label' => $r->label,
                'expense_request' => [
                    'id' => $r->expenseRequest->id,
                    'folio' => $r->expenseRequest->folio,
                    'concept_label' => $r->expenseRequest->conceptLabel(),
                    'approved_amount_cents' => $r->expenseRequest->approved_amount_cents,
                    'created_at' => $r->expenseRequest->created_at?->toIso8601String(),
                    'user' => [
                        'id' => $r->expenseRequest->user->id,
                        'name' => $r->expenseRequest->user->name,
                    ],
                ],
            ]);

        return Inertia::render('expense-requests/expense-reports/pending-review', [
            'expenseReports' => $reports,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function submit(
        SubmitExpenseReportRequest $request,
        ExpenseRequest $expenseRequest,
        SubmitExpenseReportForReview $submitReport,
        ?ExpenseReport $expenseReport = null,
    ): RedirectResponse {
        $report = $this->resolveReport($expenseRequest, $expenseReport);

        if ($report === null) {
            $report = $expenseRequest->expenseReports()
                ->where('status', ExpenseReportStatus::Rejected)
                ->orderByDesc('id')
                ->first();
        }

        try {
            $submitReport->submit(
                $expenseRequest,
                $request->user(),
                $request->integer('reported_amount_cents'),
                $request->file('pdf'),
                $request->file('xml'),
                $request->documentType(),
                $report,
                $request->filled('label') ? $request->string('label')->toString() : null,
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
        ?ExpenseReport $expenseReport = null,
    ): RedirectResponse {
        $report = $this->resolveReport($expenseRequest, $expenseReport)
            ?? $expenseRequest->expenseReports()
                ->where('status', ExpenseReportStatus::AccountingReview)
                ->orderBy('id')
                ->first();
        abort_if($report === null, 404);

        try {
            $approveReport->approve(
                $expenseRequest,
                $report,
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
            ->with('status', __('Comprobación aprobada.'));
    }

    public function reject(
        RejectExpenseReportRequest $request,
        ExpenseRequest $expenseRequest,
        RejectExpenseReport $rejectReport,
        ?ExpenseReport $expenseReport = null,
    ): RedirectResponse {
        $report = $this->resolveReport($expenseRequest, $expenseReport)
            ?? $expenseRequest->expenseReports()
                ->where('status', ExpenseReportStatus::AccountingReview)
                ->orderBy('id')
                ->first();
        abort_if($report === null, 404);

        try {
            $rejectReport->reject(
                $expenseRequest,
                $report,
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

    private function resolveReport(ExpenseRequest $expenseRequest, ?ExpenseReport $report): ?ExpenseReport
    {
        if ($report === null || ! $report->exists) {
            return null;
        }

        if ((int) $report->expense_request_id !== (int) $expenseRequest->id) {
            abort(404);
        }

        return $report;
    }
}
