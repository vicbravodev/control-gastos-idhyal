<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\CloseSettlementRequest;
use App\Http\Requests\ExpenseRequests\StoreSettlementLiquidationRequest;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Services\Settlements\CloseSettlement;
use App\Services\Settlements\Exceptions\InvalidSettlementException;
use App\Services\Settlements\RecordSettlementLiquidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseRequestSettlementController extends Controller
{
    public function pendingBalances(Request $request): Response
    {
        $this->authorize('viewPendingBalances', Settlement::class);

        $expenseRequests = ExpenseRequest::query()
            ->where('status', ExpenseRequestStatus::SettlementPending)
            ->whereHas('expenseReport.settlement', function ($query): void {
                $query->whereIn('status', [
                    SettlementStatus::PendingUserReturn,
                    SettlementStatus::PendingCompanyPayment,
                ]);
            })
            ->with(['user', 'expenseConcept', 'expenseReport.settlement'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('folio', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(15)
            ->through(fn (ExpenseRequest $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'concept_label' => $r->conceptLabel(),
                'created_at' => $r->created_at?->toIso8601String(),
                'user' => [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                ],
                'settlement' => $r->expenseReport?->settlement === null ? null : [
                    'status' => $r->expenseReport->settlement->status->value,
                    'difference_cents' => $r->expenseReport->settlement->difference_cents,
                    'basis_amount_cents' => $r->expenseReport->settlement->basis_amount_cents,
                    'reported_amount_cents' => $r->expenseReport->settlement->reported_amount_cents,
                ],
            ]);

        return Inertia::render('expense-requests/settlements/pending-balances', [
            'expenseRequests' => $expenseRequests,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function storeLiquidation(
        StoreSettlementLiquidationRequest $request,
        ExpenseRequest $expenseRequest,
        RecordSettlementLiquidation $recordSettlementLiquidation,
    ): RedirectResponse {
        try {
            $recordSettlementLiquidation->record(
                $expenseRequest,
                $request->user(),
                $request->file('evidence'),
            );
        } catch (InvalidSettlementException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['settlement' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Liquidación registrada. El solicitante fue notificado.'));
    }

    public function close(
        CloseSettlementRequest $request,
        ExpenseRequest $expenseRequest,
        CloseSettlement $closeSettlement,
    ): RedirectResponse {
        try {
            $closeSettlement->close(
                $expenseRequest,
                $request->user(),
                $request->filled('note') ? $request->string('note')->toString() : null,
            );
        } catch (InvalidSettlementException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['settlement' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Balance cerrado. El ciclo de la solicitud quedó finalizado.'));
    }
}
