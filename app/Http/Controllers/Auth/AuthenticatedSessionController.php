<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check lockout
        if ($user->isLocked()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account is temporarily locked. Try again later.',
            ]);
        }

        $request->session()->regenerate();

        // ── MFA gate for SuperAdmins ───────────────────────────────────────
        if ($user->isSuperAdmin() && $user->hasMfaEnabled()) {
            // Don't fully log them in yet — hold in session pending MFA
            $request->session()->put('mfa_pending_user_id', $user->id);
            Auth::logout(); // de-auth until TOTP is verified

            return redirect()->route('two-factor.challenge');
        }

        $user->clearFailedLogin();
        $request->session()->put('mfa_verified', ! $user->isSuperAdmin()); // non-admins skip MFA

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
