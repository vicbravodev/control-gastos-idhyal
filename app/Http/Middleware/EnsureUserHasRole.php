<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $allowedSlugs = array_filter(array_map(trim(...), explode('|', $roles)));

        foreach ($allowedSlugs as $slug) {
            if ($user->hasRoleSlug($slug)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN);
    }
}
