<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\ExpenseRequestStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\StoreExpenseRequestPaymentRequest;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Services\Payments\Exceptions\InvalidExpenseRequestPaymentException;
use App\Services\Payments\RecordExpenseRequestPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseRequestPaymentController extends Controller
{
    public function pending(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $expenseRequests = ExpenseRequest::query()
            ->where('status', ExpenseRequestStatus::PendingPayment)
            ->with(['user', 'expenseConcept'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('folio', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(15)
            ->through(fn (ExpenseRequest $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'concept_label' => $r->conceptLabel(),
                'requested_amount_cents' => $r->requested_amount_cents,
                'approved_amount_cents' => $r->approved_amount_cents,
                'created_at' => $r->created_at?->toIso8601String(),
                'user' => [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                ],
            ]);

        return Inertia::render('expense-requests/payments/pending', [
            'expenseRequests' => $expenseRequests,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function store(
        StoreExpenseRequestPaymentRequest $request,
        ExpenseRequest $expenseRequest,
        RecordExpenseRequestPayment $recordPayment,
    ): RedirectResponse {
        try {
            $recordPayment->record(
                $expenseRequest,
                $request->user(),
                $request->integer('amount_cents'),
                PaymentMethod::from($request->string('payment_method')->toString()),
                $request->date('paid_on'),
                $request->filled('transfer_reference') ? $request->string('transfer_reference')->toString() : null,
                $request->file('evidence'),
            );
        } catch (InvalidExpenseRequestPaymentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Pago registrado. El solicitante fue notificado.'));
    }
}
