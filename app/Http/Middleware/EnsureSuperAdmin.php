<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is a Super Admin (via the is_super_admin flag).
 * This is intentionally separate from Spatie's role/team system so that global
 * SuperAdmin access works regardless of tenant_id scoping.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Access restricted to Super Admins.');
        }

        return $next($request);
    }
}
