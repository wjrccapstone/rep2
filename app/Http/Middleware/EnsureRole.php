<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && ! in_array($user->role, $roles, true)) {
            $home = $user->homeRoute();

            // Bounce to the user's own landing page rather than a bare 403, unless
            // they're already headed there (avoid a redirect loop).
            if (! $request->routeIs($home)) {
                return redirect()->route($home)
                    ->with('status', "You don't have access to that page.");
            }

            abort(403);
        }

        return $next($request);
    }
}
