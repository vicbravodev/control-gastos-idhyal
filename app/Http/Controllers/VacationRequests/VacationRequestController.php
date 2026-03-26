<?php

namespace App\Http\Controllers\VacationRequests;

use App\Enums\VacationRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VacationRequests\StoreVacationRequestRequest;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use App\Services\Approvals\Exceptions\NoActiveApprovalPolicyException;
use App\Services\Approvals\VacationRequestApprovalService;
use App\Services\VacationRequests\VacationBusinessDayCounter;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use App\Services\VacationRequests\VacationRequestApprovalProgressResolver;
use App\Services\VacationRequests\VacationRequestFinalApprovalReceiptPdf;
use App\Services\VacationRequests\VacationRequestFolioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VacationRequestController extends Controller
{
    public function __construct(
        private readonly VacationEntitlementBalanceResolver $vacationBalance,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', VacationRequest::class);

        $user = auth()->user();
        $vacationRequests = VacationRequest::query()
            ->where('user_id', auth()->id())
            ->when($request->query('search'), fn ($q, $search) => $q->where('folio', 'like', "%{$search}%"))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->through(fn (VacationRequest $r) => $this->toListItem($r));

        return Inertia::render('vacation-requests/index', [
            'vacationRequests' => $vacationRequests,
            'vacationBalance' => $user !== null
                ? $this->vacationBalance->resolveForUser($user)
                : null,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
            ],
            'available_statuses' => array_map(
                static fn (VacationRequestStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                VacationRequestStatus::cases(),
            ),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', VacationRequest::class);

        $user = auth()->user();

        return Inertia::render('vacation-requests/create', [
            'vacationBalance' => $user !== null
                ? $this->vacationBalance->resolveForUser($user)
                : null,
        ]);
    }

    public function store(
        StoreVacationRequestRequest $request,
        VacationBusinessDayCounter $businessDays,
        VacationRequestFolioGenerator $folioGenerator,
        VacationRequestApprovalService $approvalService,
    ): RedirectResponse {
        $startsOn = $request->date('starts_on');
        $endsOn = $request->date('ends_on');
        $businessDaysCount = $businessDays->countInclusive($startsOn, $endsOn);

        try {
            $vacationRequest = DB::transaction(function () use ($request, $businessDaysCount, $folioGenerator, $approvalService): VacationRequest {
                $vacationRequest = VacationRequest::query()->create([
                    'user_id' => $request->user()->id,
                    'status' => VacationRequestStatus::Submitted,
                    'folio' => null,
                    'starts_on' => $request->date('starts_on'),
                    'ends_on' => $request->date('ends_on'),
                    'business_days_count' => $businessDaysCount,
                ]);

                $folioGenerator->assign($vacationRequest);
                $approvalService->startWorkflow($vacationRequest);
                $vacationRequest->refresh();

                return $vacationRequest;
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

        return redirect()
            ->route('vacation-requests.show', $vacationRequest)
            ->with('status', __('Solicitud creada y enviada a aprobación.'));
    }

    public function show(
        VacationRequest $vacation_request,
        VacationRequestApprovalService $approvalService,
        VacationRequestApprovalProgressResolver $approvalProgress,
    ): InertiaResponse {
        $this->authorize('view', $vacation_request);

        $vacation_request->load(['approvals.role', 'user']);
        $user = auth()->user();

        return Inertia::render('vacation-requests/show', [
            'vacationRequest' => array_merge($this->toDetail($vacation_request), [
                'approval_progress' => $approvalProgress->snapshot($vacation_request),
            ]),
            'canDownloadFinalApprovalReceipt' => $user?->can('downloadFinalApprovalReceipt', $vacation_request) ?? false,
            'activeApprovalId' => $this->activeApprovalIdForUser($vacation_request, $user, $approvalService),
        ]);
    }

    public function downloadFinalApprovalReceipt(
        VacationRequest $vacation_request,
        VacationRequestFinalApprovalReceiptPdf $pdf,
    ): Response {
        $this->authorize('downloadFinalApprovalReceipt', $vacation_request);

        return $pdf->download($vacation_request);
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(VacationRequest $r): array
    {
        return [
            'id' => $r->id,
            'folio' => $r->folio,
            'status' => $r->status->value,
            'starts_on' => $r->starts_on?->toDateString(),
            'ends_on' => $r->ends_on?->toDateString(),
            'business_days_count' => $r->business_days_count,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDetail(VacationRequest $r): array
    {
        return [
            'id' => $r->id,
            'folio' => $r->folio,
            'status' => $r->status->value,
            'starts_on' => $r->starts_on?->toDateString(),
            'ends_on' => $r->ends_on?->toDateString(),
            'business_days_count' => $r->business_days_count,
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

    private function activeApprovalIdForUser(
        VacationRequest $vacationRequest,
        ?User $user,
        VacationRequestApprovalService $approvalService,
    ): ?int {
        if ($user === null) {
            return null;
        }

        foreach ($vacationRequest->approvals as $approval) {
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
