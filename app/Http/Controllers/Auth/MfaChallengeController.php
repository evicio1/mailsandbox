<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class MfaChallengeController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        // If no pending MFA user, redirect to login
        if (! $request->session()->has('mfa_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('mfa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        // ── Try TOTP code first ──────────────────────────────────────────
        if ($request->filled('code')) {
            $secret = decrypt($user->two_factor_secret);
            $valid  = $this->google2fa->verifyKey($secret, $request->code);

            if (! $valid) {
                return back()->withErrors(['code' => 'Invalid authentication code.']);
            }
        }

        // ── Try recovery code ────────────────────────────────────────────
        elseif ($request->filled('recovery_code')) {
            $recoveryCodes = $user->getRecoveryCodes();
            $index = array_search(strtoupper(trim($request->recovery_code)), $recoveryCodes, true);

            if ($index === false) {
                return back()->withErrors(['recovery_code' => 'Invalid recovery code.']);
            }

            // Invalidate used code (one-time use)
            unset($recoveryCodes[$index]);
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
            ]);
        } else {
            return back()->withErrors(['code' => 'Please enter a code or recovery code.']);
        }

        // All good — log the user in properly
        auth()->login($user);

        $request->session()->forget('mfa_pending_user_id');
        $request->session()->put('mfa_verified', true);
        $request->session()->regenerate();

        $user->clearFailedLogin();

        return redirect()->intended(route('dashboard'));
    }
}
