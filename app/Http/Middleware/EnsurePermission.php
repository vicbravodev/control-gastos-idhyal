<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route by permission slug. Accepts pipe-separated alternatives:
 *   middleware(['auth', 'permission:admin.roles.manage|admin.users.manage'])
 */
class EnsurePermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $slugs = array_filter(array_map(trim(...), explode('|', $permissions)));

        foreach ($slugs as $slug) {
            if ($user->hasPermission($slug)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN);
    }
}
