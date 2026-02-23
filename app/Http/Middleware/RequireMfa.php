<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces MFA verification for SuperAdmin users.
 * If a super admin has MFA configured but has not yet passed the TOTP challenge
 * in this session, they are redirected to the challenge screen.
 */
class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Only enforce MFA for super admins
        if (! $user->isSuperAdmin()) {
            return $next($request);
        }

        // Super admin has MFA configured but not yet verified in this session
        if ($user->hasMfaEnabled() && ! $request->session()->get('mfa_verified')) {
            // Avoid redirect loop
            if (! $request->routeIs('two-factor.*')) {
                return redirect()->route('two-factor.challenge');
            }
        }

        // Super admin hasn't set up MFA yet — force enrollment
        if (! $user->hasMfaEnabled() && ! $request->routeIs('two-factor.*') && ! $request->routeIs('logout')) {
            return redirect()->route('two-factor.enroll')
                ->with('warning', 'You must set up two-factor authentication to continue.');
        }

        return $next($request);
    }
}
