<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecoveryCodeController extends Controller
{
    /**
     * Regenerate all recovery codes (destroys old ones).
     */
    public function store(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user  = $request->user();
        $codes = $user->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return redirect()->route('two-factor.recovery-codes')
            ->with('recovery_codes', $codes)
            ->with('success', 'Recovery codes regenerated. Save these somewhere safe!');
    }
}
