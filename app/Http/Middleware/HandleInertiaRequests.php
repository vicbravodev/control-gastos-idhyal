<?php

namespace App\Http\Middleware;

use App\Enums\UserGender;
use App\Models\ApprovalPolicy;
use App\Models\VacationRule;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $authUser = null;

        if ($user !== null) {
            $user->loadMissing(['role', 'role.permissions:id,slug']);
            $payload = $user->toArray();
            $payload['role'] = $user->role !== null
                ? $user->role->only(['id', 'slug', 'name'])
                : null;
            $payload['permissions'] = $user->permissionSlugs();
            $payload['has_expense_request_oversight'] = $user->hasExpenseRequestOversight();
            $payload['has_vacation_request_oversight'] = $user->hasVacationRequestOversight();
            $payload['can_manage_budgets'] = $user->hasPermission('budget.manage');
            $payload['can_manage_approval_policies'] = $user->can('viewAny', ApprovalPolicy::class);
            $payload['can_manage_vacation_rules'] = $user->can('viewAny', VacationRule::class);
            $payload['can_manage_roles'] = $user->hasPermission('admin.roles.manage');
            $payload['can_view_reports'] = $user->hasPermission('report.expenses.view');
            $payload['unread_notifications_count'] = $user->unreadNotifications()->count();
            $payload['greeting'] = ($user->gender ?? UserGender::PreferNotToSay)->greeting();
            $authUser = $payload;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $authUser,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'status' => fn (): mixed => $request->session()->get('status'),
            ],
        ];
    }
}
