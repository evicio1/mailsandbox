<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AttachmentController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Core routes for all users
    Route::get('/dashboard', [MailboxController::class, 'index'])->name('dashboard');
    Route::get('/mailboxes/{mailbox}', [MailboxController::class, 'show'])->name('mailboxes.show');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');

    // Example of a route requiring an active Stripe Subscription
    Route::middleware('subscribed')->group(function () {
        // e.g., Route::get('/premium-features', ...);
    });

    // Example of a route requiring a specific Spatie Role
    Route::middleware('role:admin')->group(function () {
        // e.g., Route::get('/admin/billing', ...);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
