<?php

namespace App\Http\Controllers\ExpenseRequests;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequests\CancelExpenseRequestRequest;
use App\Http\Requests\ExpenseRequests\StoreExpenseRequestRequest;
use App\Http\Requests\ExpenseRequests\StoreExpenseRequestSubmissionAttachmentsRequest;
use App\Http\Requests\ExpenseRequests\UpdateExpenseRequestRequest;
use App\Models\Attachment;
use App\Models\DocumentEvent;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\Approvals\ExpenseRequestApprovalService;
use App\Services\ExpenseReports\ExpenseReportAttachmentWriter;
use App\Services\ExpenseRequests\CancelExpenseRequest;
use App\Services\ExpenseRequests\ExpenseRequestApprovalProgressResolver;
use App\Services\ExpenseRequests\ExpenseRequestDocumentEventTimelinePresenter;
use App\Services\ExpenseRequests\ExpenseRequestExpenseReportVerificationReceiptPdf;
use App\Services\ExpenseRequests\ExpenseRequestFinalApprovalReceiptPdf;
use App\Services\ExpenseRequests\ExpenseRequestFolioGenerator;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use App\Services\ExpenseRequests\ExpenseRequestPaymentReceiptPdf;
use App\Services\ExpenseRequests\ExpenseRequestSettlementLiquidationReceiptPdf;
use App\Services\ExpenseRequests\ExpenseRequestSubmissionAttachmentWriter;
use App\Services\ExpenseRequests\ExpenseRequestSubmissionReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ExpenseRequestController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', ExpenseRequest::class);

        $expenseRequests = ExpenseRequest::query()
            ->where('user_id', auth()->id())
            ->with('expenseConcept')
            ->when($request->query('search'), fn ($q, $search) => $q->where('folio', 'like', "%{$search}%"))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->through(fn (ExpenseRequest $r) => $this->toListItem($r));

        return Inertia::render('expense-requests/index', [
            'expenseRequests' => $expenseRequests,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
            ],
            'available_statuses' => array_map(
                static fn (ExpenseRequestStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                ExpenseRequestStatus::cases(),
            ),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', ExpenseRequest::class);

        return Inertia::render('expense-requests/create', [
            'expenseConcepts' => ExpenseConcept::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StoreExpenseRequestRequest $request,
        ExpenseRequestFolioGenerator $folioGenerator,
        ExpenseRequestApprovalService $approvalService,
        ExpenseRequestNotificationDispatcher $notificationDispatcher,
        ExpenseRequestSubmissionAttachmentWriter $submissionAttachments,
    ): RedirectResponse {
        try {
            $expenseRequest = DB::transaction(function () use ($request, $folioGenerator, $approvalService, $submissionAttachments): ExpenseRequest {
                $expenseRequest = ExpenseRequest::query()->create([
                    'user_id' => $request->user()->id,
                    'status' => ExpenseRequestStatus::Submitted,
                    'folio' => null,
                    'requested_amount_cents' => $request->integer('requested_amount_cents'),
                    'approved_amount_cents' => null,
                    'expense_concept_id' => $request->integer('expense_concept_id'),
                    'concept_description' => $request->filled('concept_description')
                        ? $request->string('concept_description')->toString()
                        : null,
                    'delivery_method' => $request->input('delivery_method'),
                ]);

                $folioGenerator->assign($expenseRequest);
                $approvalService->startWorkflow($expenseRequest);

                $expenseRequest = $expenseRequest->fresh();

                DocumentEvent::query()->create([
                    'subject_type' => $expenseRequest->getMorphClass(),
                    'subject_id' => $expenseRequest->getKey(),
                    'event_type' => DocumentEventType::ExpenseRequestSubmitted,
                    'actor_user_id' => $request->user()->id,
                    'note' => '-',
                    'metadata' => [
                        'folio' => $expenseRequest->folio,
                    ],
                ]);

                $files = $request->file('attachments', []);
                if (is_array($files) && $files !== []) {
                    $submissionAttachments->attachMany(
                        $expenseRequest,
                        $request->user(),
                        $files,
                    );
                }

                return $expenseRequest;
            });
        } catch (NoActiveApprovalPolicyException) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'approval_policy' => __('No hay una política de aprobación activa para tu rol.'),
                ]);
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'approval_policy' => $e->getMessage(),
                ]);
        }

        $notificationDispatcher->notifyApproversOnSubmitted(
            $expenseRequest->load(['user', 'approvals']),
        );

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Solicitud creada y enviada a aprobación.'));
    }

    public function show(
        ExpenseRequest $expenseRequest,
        ExpenseRequestApprovalService $approvalService,
        ExpenseRequestApprovalProgressResolver $approvalProgress,
        ExpenseReportAttachmentWriter $expenseReportAttachments,
        ExpenseRequestDocumentEventTimelinePresenter $documentEventTimeline,
    ): InertiaResponse {
        $this->authorize('view', $expenseRequest);

        $expenseRequest->load([
            'approvals.role',
            'user',
            'expenseConcept',
            'attachments',
            'payments.recordedBy',
            'payments.attachments',
            'expenseReport.attachments',
            'expenseReport.settlement.attachments',
            'documentEvents.actor',
        ]);
        $user = auth()->user();

        $paymentEvidenceAttachment = $expenseRequest->payments
            ->sortBy('id')
            ->first()
            ?->attachments
            ->sortBy('id')
            ->first();

        $verificationPdfAttachment = $expenseRequest->expenseReport !== null
            ? $expenseReportAttachments->findVerificationAttachment($expenseRequest->expenseReport, 'pdf')
            : null;
        $verificationXmlAttachment = $expenseRequest->expenseReport !== null
            ? $expenseReportAttachments->findVerificationAttachment($expenseRequest->expenseReport, 'xml')
            : null;

        return Inertia::render('expense-requests/show', [
            'expenseRequest' => array_merge($this->toDetail($expenseRequest), [
                'approval_progress' => $approvalProgress->snapshot($expenseRequest),
                'payment' => $this->toPaymentSummary($expenseRequest),
                'expense_report' => $this->toExpenseReportPayload($expenseRequest, $expenseReportAttachments),
                'settlement' => $this->toSettlementSummary($expenseRequest),
                'submission_attachments' => $this->toSubmissionAttachmentsPayload($expenseRequest, $user),
                'document_timeline' => $documentEventTimeline->present(Collection::make($expenseRequest->documentEvents)),
            ]),
            'canUpdate' => $user?->can('update', $expenseRequest) ?? false,
            'canDownloadSubmissionReceipt' => $user?->can('downloadSubmissionReceipt', $expenseRequest) ?? false,
            'canDownloadFinalApprovalReceipt' => $user?->can('downloadFinalApprovalReceipt', $expenseRequest) ?? false,
            'canDownloadPaymentReceipt' => $user?->can('downloadPaymentReceipt', $expenseRequest) ?? false,
            'canDownloadSettlementLiquidationReceipt' => $user?->can('downloadSettlementLiquidationReceipt', $expenseRequest) ?? false,
            'canDownloadExpenseReportVerificationReceipt' => $user?->can('downloadExpenseReportVerificationReceipt', $expenseRequest) ?? false,
            'canDownloadExpenseReportVerificationPdf' => $verificationPdfAttachment !== null
                && ($user?->can('downloadExpenseReportVerificationAttachment', [$expenseRequest, $verificationPdfAttachment]) ?? false),
            'canDownloadExpenseReportVerificationXml' => $verificationXmlAttachment !== null
                && ($user?->can('downloadExpenseReportVerificationAttachment', [$expenseRequest, $verificationXmlAttachment]) ?? false),
            'canDownloadPaymentEvidence' => $paymentEvidenceAttachment !== null
                && ($user?->can('downloadPaymentEvidence', [$expenseRequest, $paymentEvidenceAttachment]) ?? false),
            'canRecordPayment' => $user?->can('recordPayment', $expenseRequest) ?? false,
            'canSaveExpenseReportDraft' => $user?->can('saveExpenseReportDraft', $expenseRequest) ?? false,
            'canSubmitExpenseReport' => $user?->can('submitExpenseReport', $expenseRequest) ?? false,
            'canReviewExpenseReport' => $user?->can('reviewExpenseReport', $expenseRequest) ?? false,
            'canRecordSettlementLiquidation' => $user?->can('recordSettlementLiquidation', $expenseRequest) ?? false,
            'canCloseSettlement' => $user?->can('closeSettlement', $expenseRequest) ?? false,
            'canCancel' => $user?->can('cancel', $expenseRequest) ?? false,
            'canAddSubmissionAttachments' => $user?->can('addSubmissionAttachments', $expenseRequest) ?? false,
            'activeApprovalId' => $user instanceof User
                ? $this->activeApprovalIdForUser($expenseRequest, $user, $approvalService)
                : null,
        ]);
    }

    public function downloadSubmissionReceipt(
        ExpenseRequest $expense_request,
        ExpenseRequestSubmissionReceiptPdf $receiptPdf,
    ): Response {
        $this->authorize('downloadSubmissionReceipt', $expense_request);

        return $receiptPdf->download($expense_request);
    }

    public function downloadFinalApprovalReceipt(
        ExpenseRequest $expense_request,
        ExpenseRequestFinalApprovalReceiptPdf $receiptPdf,
    ): Response {
        $this->authorize('downloadFinalApprovalReceipt', $expense_request);

        return $receiptPdf->download($expense_request);
    }

    public function downloadPaymentReceipt(
        ExpenseRequest $expense_request,
        ExpenseRequestPaymentReceiptPdf $receiptPdf,
    ): Response {
        $this->authorize('downloadPaymentReceipt', $expense_request);

        return $receiptPdf->download($expense_request);
    }

    public function downloadSettlementLiquidationReceipt(
        ExpenseRequest $expense_request,
        ExpenseRequestSettlementLiquidationReceiptPdf $receiptPdf,
    ): Response {
        $this->authorize('downloadSettlementLiquidationReceipt', $expense_request);

        return $receiptPdf->download($expense_request);
    }

    public function downloadSettlementLiquidationEvidence(
        ExpenseRequest $expense_request,
        Attachment $attachment,
    ): SymfonyResponse {
        $this->authorize('downloadSettlementLiquidationEvidence', [$expense_request, $attachment]);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename,
        );
    }

    public function downloadPaymentEvidence(
        ExpenseRequest $expense_request,
        Attachment $attachment,
    ): SymfonyResponse {
        $this->authorize('downloadPaymentEvidence', [$expense_request, $attachment]);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename,
        );
    }

    public function downloadExpenseReportVerificationAttachment(
        ExpenseRequest $expense_request,
        Attachment $attachment,
    ): SymfonyResponse {
        $this->authorize('downloadExpenseReportVerificationAttachment', [$expense_request, $attachment]);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename,
        );
    }

    public function downloadExpenseReportVerificationReceipt(
        ExpenseRequest $expense_request,
        ExpenseRequestExpenseReportVerificationReceiptPdf $receiptPdf,
    ): Response {
        $this->authorize('downloadExpenseReportVerificationReceipt', $expense_request);

        return $receiptPdf->download($expense_request);
    }

    public function edit(ExpenseRequest $expenseRequest): InertiaResponse
    {
        $this->authorize('update', $expenseRequest);

        $expenseRequest->load('attachments');

        return Inertia::render('expense-requests/edit', [
            'expenseRequest' => $this->toFormPayload($expenseRequest),
            'expenseConcepts' => ExpenseConcept::query()
                ->where(function ($q) use ($expenseRequest): void {
                    $q->where('is_active', true)
                        ->orWhere('id', $expenseRequest->expense_concept_id);
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
        ]);
    }

    public function update(
        UpdateExpenseRequestRequest $request,
        ExpenseRequest $expenseRequest,
        ExpenseRequestSubmissionAttachmentWriter $submissionAttachments,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $expenseRequest, $submissionAttachments): void {
            $expenseRequest->update([
                'requested_amount_cents' => $request->integer('requested_amount_cents'),
                'expense_concept_id' => $request->integer('expense_concept_id'),
                'concept_description' => $request->filled('concept_description')
                    ? $request->string('concept_description')->toString()
                    : null,
                'delivery_method' => $request->input('delivery_method'),
            ]);

            $files = $request->file('attachments', []);
            if (is_array($files) && $files !== []) {
                $submissionAttachments->attachMany(
                    $expenseRequest,
                    $request->user(),
                    $files,
                );
            }
        });

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Solicitud actualizada.'));
    }

    public function storeSubmissionAttachments(
        StoreExpenseRequestSubmissionAttachmentsRequest $request,
        ExpenseRequest $expense_request,
        ExpenseRequestSubmissionAttachmentWriter $submissionAttachments,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $expense_request, $submissionAttachments): void {
            $files = $request->file('attachments', []);
            if (! is_array($files) || $files === []) {
                return;
            }
            $submissionAttachments->attachMany(
                $expense_request,
                $request->user(),
                $files,
            );
        });

        return redirect()
            ->route('expense-requests.show', $expense_request)
            ->with('status', __('Archivos adjuntos actualizados.'));
    }

    public function destroySubmissionAttachment(
        ExpenseRequest $expense_request,
        Attachment $attachment,
        ExpenseRequestSubmissionAttachmentWriter $submissionAttachments,
    ): RedirectResponse {
        $this->authorize('deleteSubmissionAttachment', [$expense_request, $attachment]);

        $submissionAttachments->deleteAttachment($expense_request, $attachment);

        return redirect()
            ->route('expense-requests.show', $expense_request)
            ->with('status', __('Archivo eliminado.'));
    }

    public function downloadSubmissionAttachment(
        ExpenseRequest $expense_request,
        Attachment $attachment,
    ): SymfonyResponse {
        $this->authorize('downloadSubmissionAttachment', [$expense_request, $attachment]);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename,
        );
    }

    public function cancel(
        CancelExpenseRequestRequest $request,
        ExpenseRequest $expenseRequest,
        CancelExpenseRequest $cancelExpenseRequest,
    ): RedirectResponse {
        try {
            $cancelExpenseRequest->cancel(
                $expenseRequest,
                $request->user(),
                $request->string('note')->toString(),
            );
        } catch (InvalidApprovalStateException $e) {
            return redirect()
                ->back()
                ->withErrors(['note' => $e->getMessage()]);
        }

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('status', __('Solicitud cancelada.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(ExpenseRequest $r): array
    {
        return [
            'id' => $r->id,
            'folio' => $r->folio,
            'status' => $r->status->value,
            'requested_amount_cents' => $r->requested_amount_cents,
            'concept_label' => $r->conceptLabel(),
            'concept_description' => $r->concept_description,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDetail(ExpenseRequest $r): array
    {
        return [
            'id' => $r->id,
            'folio' => $r->folio,
            'status' => $r->status->value,
            'requested_amount_cents' => $r->requested_amount_cents,
            'approved_amount_cents' => $r->approved_amount_cents,
            'concept_label' => $r->conceptLabel(),
            'concept_description' => $r->concept_description,
            'delivery_method' => $r->delivery_method->value,
            'created_at' => $r->created_at?->toIso8601String(),
            'user' => [
                'id' => $r->user->id,
                'name' => $r->user->name,
            ],
            'approvals' => $r->approvals->sortBy('step_order')->values()->map(fn ($a) => [
                'id' => $a->id,
                'step_order' => $a->step_order,
                'status' => $a->status->value,
                'role' => [
                    'id' => $a->role->id,
                    'name' => $a->role->name,
                    'slug' => $a->role->slug,
                ],
                'note' => $a->note,
                'acted_at' => $a->acted_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toFormPayload(ExpenseRequest $r): array
    {
        $attachments = $r->relationLoaded('attachments')
            ? $r->attachments
            : collect();

        return [
            'id' => $r->id,
            'requested_amount_cents' => $r->requested_amount_cents,
            'expense_concept_id' => $r->expense_concept_id,
            'concept_description' => $r->concept_description,
            'delivery_method' => $r->delivery_method->value,
            'submission_attachments' => $attachments->sortBy('id')->values()->map(fn (Attachment $a) => [
                'id' => $a->id,
                'original_filename' => $a->original_filename,
            ])->all(),
        ];
    }

    /**
     * @return list<array{id: int, original_filename: string, mime_type: string|null, size_bytes: int|null, can_download: bool, can_delete: bool}>
     */
    private function toSubmissionAttachmentsPayload(ExpenseRequest $expenseRequest, ?User $user): array
    {
        $attachments = $expenseRequest->relationLoaded('attachments')
            ? $expenseRequest->attachments
            : $expenseRequest->attachments()->orderBy('id')->get();

        return $attachments->sortBy('id')->values()->map(function (Attachment $a) use ($user, $expenseRequest): array {
            return [
                'id' => $a->id,
                'original_filename' => $a->original_filename,
                'mime_type' => $a->mime_type,
                'size_bytes' => $a->size_bytes,
                'can_download' => $user instanceof User
                    && $user->can('downloadSubmissionAttachment', [$expenseRequest, $a]),
                'can_delete' => $user instanceof User
                    && $user->can('deleteSubmissionAttachment', [$expenseRequest, $a]),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function toPaymentSummary(ExpenseRequest $expenseRequest): ?array
    {
        $payment = $expenseRequest->payments->sortBy('id')->first();
        if ($payment === null) {
            return null;
        }

        $attachment = $payment->attachments->first();

        return [
            'id' => $payment->id,
            'amount_cents' => $payment->amount_cents,
            'payment_method' => $payment->payment_method->value,
            'paid_on' => $payment->paid_on->toDateString(),
            'transfer_reference' => $payment->transfer_reference,
            'recorded_by' => $payment->recordedBy->name,
            'evidence_original_filename' => $attachment?->original_filename,
            'evidence_attachment_id' => $attachment?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function toExpenseReportPayload(
        ExpenseRequest $expenseRequest,
        ExpenseReportAttachmentWriter $writer,
    ): ?array {
        $report = $expenseRequest->expenseReport;
        if ($report === null) {
            return null;
        }

        return [
            'id' => $report->id,
            'status' => $report->status->value,
            'reported_amount_cents' => $report->reported_amount_cents,
            'submitted_at' => $report->submitted_at?->toIso8601String(),
            'has_pdf_and_xml' => $writer->hasPdfAndXml($report),
            'verification_pdf_attachment_id' => $writer->findVerificationAttachment($report, 'pdf')?->id,
            'verification_xml_attachment_id' => $writer->findVerificationAttachment($report, 'xml')?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function toSettlementSummary(ExpenseRequest $expenseRequest): ?array
    {
        $settlement = $expenseRequest->expenseReport?->settlement;
        if ($settlement === null) {
            return null;
        }

        $liquidationAttachment = $settlement->relationLoaded('attachments')
            ? $settlement->attachments->sortBy('id')->first()
            : $settlement->attachments()->orderBy('id')->first();

        return [
            'id' => $settlement->id,
            'status' => $settlement->status->value,
            'basis_amount_cents' => $settlement->basis_amount_cents,
            'reported_amount_cents' => $settlement->reported_amount_cents,
            'difference_cents' => $settlement->difference_cents,
            'liquidation_evidence_original_filename' => $liquidationAttachment?->original_filename,
            'liquidation_evidence_attachment_id' => $liquidationAttachment?->id,
        ];
    }

    private function activeApprovalIdForUser(
        ExpenseRequest $expenseRequest,
        User $user,
        ExpenseRequestApprovalService $approvalService,
    ): ?int {
        foreach ($expenseRequest->approvals as $approval) {
            if (! $approvalService->isPendingStepActive($approval)) {
                continue;
            }
            if (! $user->can('approve', $approval)) {
                continue;
            }

            return $approval->id;
        }

        return null;
    }
}
