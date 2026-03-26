<?php

namespace App\Http\Middleware;

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
            $user->loadMissing('role');
            $payload = $user->toArray();
            $payload['role'] = $user->role !== null
                ? $user->role->only(['id', 'slug', 'name'])
                : null;
            $payload['has_expense_request_oversight'] = $user->hasExpenseRequestOversight();
            $payload['can_manage_budgets'] = $user->canManageBudgetsAndPolicies();
            $payload['can_manage_approval_policies'] = $user->can('viewAny', \App\Models\ApprovalPolicy::class);
            $payload['can_manage_vacation_rules'] = $user->can('viewAny', \App\Models\VacationRule::class);
            $payload['can_view_reports'] = $user->hasRole(\App\Enums\RoleSlug::Contabilidad) || $user->hasRole(\App\Enums\RoleSlug::SuperAdmin);
            $payload['unread_notifications_count'] = $user->unreadNotifications()->count();
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
