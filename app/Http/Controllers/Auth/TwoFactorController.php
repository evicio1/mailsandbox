<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─── Enroll ──────────────────────────────────────────────────────────────

    public function showEnroll(Request $request)
    {
        $user = $request->user();

        // Generate a new secret if not yet started enrollment
        if (! $user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->update(['two_factor_secret' => encrypt($secret)]);
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Use BaconQrCode to render the SVG inline
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $qrCodeSvg = (new \BaconQrCode\Writer($renderer))->writeString($qrCodeUrl);

        return view('auth.two-factor-enroll', [
            'secret'    => $secret,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    public function confirmEnroll(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user   = $request->user();
        $secret = decrypt($user->two_factor_secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        // Generate & store recovery codes
        $codes = $user->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
            'two_factor_confirmed_at'   => now(),
        ]);

        // Mark MFA as verified for this session
        $request->session()->put('mfa_verified', true);

        return redirect()->route('two-factor.recovery-codes')
            ->with('recovery_codes', $codes)
            ->with('success', 'Two-factor authentication has been enabled!');
    }

    // ─── Disable ─────────────────────────────────────────────────────────────

    public function showDisable()
    {
        return view('auth.two-factor-disable');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        $request->session()->forget('mfa_verified');

        return redirect()->route('profile.edit')
            ->with('success', 'Two-factor authentication disabled.');
    }

    // ─── Recovery Codes ──────────────────────────────────────────────────────

    public function showRecoveryCodes(Request $request)
    {
        $codes = $request->session()->get('recovery_codes', []);
        if (empty($codes)) {
            $codes = $request->user()->getRecoveryCodes();
        }
        return view('auth.two-factor-recovery-codes', compact('codes'));
    }
}
