<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();
        if (!in_array($user->getRole()->value, $roles)) {
            return response()->json([
                'error' => 'Access denied. You do not have the required role.',
            ], 403);
        }
        return $next($request);
    }
}
