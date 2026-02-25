<?php

use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Auth\MfaChallengeController;
use App\Http\Controllers\Auth\RecoveryCodeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

// ─── Root ─────────────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── MFA Routes (auth'd but before MFA gate) ─────────────────────────────────
Route::middleware('auth')->group(function () {
    // Enrollment (SuperAdmin only but gated in controller)
    Route::get('/two-factor/enroll',   [TwoFactorController::class, 'showEnroll'])->name('two-factor.enroll');
    Route::post('/two-factor/enroll',  [TwoFactorController::class, 'confirmEnroll'])->name('two-factor.enroll.confirm');
    Route::get('/two-factor/disable',  [TwoFactorController::class, 'showDisable'])->name('two-factor.disable');
    Route::delete('/two-factor',       [TwoFactorController::class, 'disable'])->name('two-factor.destroy');
    Route::get('/two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes', [RecoveryCodeController::class, 'store'])->name('two-factor.recovery-codes.regenerate');

});

// MFA Challenge (also used for pending login — user not yet fully authed)
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge',  [MfaChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [MfaChallengeController::class, 'verify'])->name('two-factor.challenge.verify');
});

// ─── Authenticated + Tenant Active + MFA-verified ─────────────────────────────
Route::middleware(['auth', 'verified', 'tenant.active', 'mfa'])->group(function () {

    // ── Dashboard / Core ──────────────────────────────────────────────────
    Route::get('/dashboard', function () {
        $user   = auth()->user();
        $tenant = $user->tenant;

        $mailboxQuery = $tenant ? $tenant->mailboxes() : \App\Models\Mailbox::query();
        $messageQuery = $tenant
            ? \App\Models\Message::whereIn('mailbox_id', $tenant->mailboxes()->pluck('id'))
            : \App\Models\Message::query();

        $stats = [
            'mailboxes' => (clone $mailboxQuery)->count(),
            'messages'  => (clone $messageQuery)->count(),
            'unread'    => (clone $messageQuery)->where('is_read', false)->count(),
        ];

        $recentMailboxes = (clone $mailboxQuery)->orderByDesc('updated_at')->limit(8)->get();

        return view('dashboard', compact('stats', 'recentMailboxes'));
    })->name('dashboard');

    Route::get('/mailboxes',             [\App\Http\Controllers\MailboxController::class, 'index'])->name('mailboxes.index');
    Route::post('/mailboxes',            [\App\Http\Controllers\MailboxController::class, 'store'])->name('mailboxes.store');
    Route::get('/mailboxes/{mailbox}',   [MailboxController::class, 'show'])->name('mailboxes.show');
    Route::get('/messages/{message}',    [MessageController::class, 'show'])->name('messages.show');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');

    // ── Profile + Sessions ─────────────────────────────────────────────────
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/sessions',  [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

    // ── Billing ────────────────────────────────────────────────────────────
    Route::middleware('tenant.admin')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    });

    // ── Admin: Tenant Management (SuperAdmin only) ─────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('tenants', TenantController::class);
        Route::post('tenants/{tenant}/suspend',       [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate',      [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/resend-invite', [TenantController::class, 'resendInvite'])->name('tenants.resend-invite');
    });

    // ── Admin: Member Management (TenantAdmin or SuperAdmin) ─────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('tenants/{tenant}/members',              [MemberController::class, 'index'])->name('members.index');
        Route::put('tenants/{tenant}/members/{user}',       [MemberController::class, 'update'])->name('members.update');
        Route::delete('tenants/{tenant}/members/{user}',    [MemberController::class, 'destroy'])->name('members.destroy');
    });
});

require __DIR__.'/auth.php';

// ── Stripe Webhooks ────────────────────────────────────────────────────────
Route::post('stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])->name('cashier.webhook');
