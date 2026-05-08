<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    /**
     * Async search endpoint for the approval-policy form (specific-user picker).
     */
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('approval_policy.manage') ?? false,
            403,
        );

        $q = trim((string) $request->query('q', ''));
        $limit = min(50, max(5, (int) $request->query('limit', 20)));

        $users = User::query()
            ->when($q !== '', fn ($builder) => $builder->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%");
            }))
            ->with('role:id,name')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'role_id']);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_label' => $u->role?->name,
            ])->values(),
        ]);
    }
}
