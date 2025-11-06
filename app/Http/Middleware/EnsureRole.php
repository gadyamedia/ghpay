<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, ?string $roles = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED)
                : redirect()->route('login');
        }

        if (! $roles) {
            return $next($request);
        }

        $allowed = array_map('trim', explode(',', $roles));

        if (! $user->isRole($allowed)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN)
                : abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
