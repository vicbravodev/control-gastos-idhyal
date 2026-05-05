<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreVacationEntitlementAdjustmentRequest;
use App\Models\DocumentEvent;
use App\Models\User;
use App\Models\VacationEntitlementAdjustment;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserVacationAdjustmentController extends Controller
{
    public function __construct(
        private readonly VacationEntitlementBalanceResolver $balance,
    ) {}

    public function index(User $user): Response
    {
        $this->authorize('manageStaffDirectory', User::class);

        $adjustments = VacationEntitlementAdjustment::query()
            ->where('user_id', $user->id)
            ->with('grantedBy:id,name')
            ->orderByDesc('id')
            ->get()
            ->map(fn (VacationEntitlementAdjustment $a): array => [
                'id' => $a->id,
                'calendar_year' => $a->calendar_year,
                'days' => $a->days,
                'reason' => $a->reason,
                'granted_by' => $a->grantedBy?->name,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users/vacation-adjustments', [
            'staffUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'adjustments' => $adjustments,
            'balance' => $this->balance->resolveForUser($user),
            'currentYear' => (int) now()->year,
        ]);
    }

    public function store(
        StoreVacationEntitlementAdjustmentRequest $request,
        User $user,
    ): RedirectResponse {
        $actor = $request->user();

        DB::transaction(function () use ($request, $user, $actor): void {
            $adjustment = VacationEntitlementAdjustment::query()->create([
                'user_id' => $user->id,
                'calendar_year' => $request->integer('calendar_year'),
                'days' => $request->integer('days'),
                'reason' => $request->string('reason')->toString(),
                'granted_by_user_id' => $actor->id,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $user->getMorphClass(),
                'subject_id' => $user->getKey(),
                'event_type' => DocumentEventType::VacationEntitlementAdjusted,
                'actor_user_id' => $actor->id,
                'note' => $request->string('reason')->toString(),
                'metadata' => [
                    'adjustment_id' => $adjustment->id,
                    'calendar_year' => $adjustment->calendar_year,
                    'days' => $adjustment->days,
                ],
            ]);
        });

        return redirect()
            ->route('admin.users.vacation-adjustments.index', $user)
            ->with('status', __('Ajuste de vacaciones registrado.'));
    }
}
