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

        if (! $user || ! $user->staff) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get role from Spatie (first role assigned to user)
        $userRole = $user->roles()->first()?->name;

        if (! $userRole || ! in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
            ], 403);
        }

        return $next($request);
    }
}
