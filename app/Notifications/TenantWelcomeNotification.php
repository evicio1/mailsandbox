<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class TenantWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Tenant $tenant) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Generate a password reset link so the user can set their own password
        $resetToken = Password::createToken($notifiable);
        $resetUrl   = url(route('password.reset', [
            'token' => $resetToken,
            'email' => $notifiable->email,
        ], false));

        return (new MailMessage)
            ->subject("Welcome to {$this->tenant->name} on " . config('app.name'))
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've been added as the **admin** of the **{$this->tenant->name}** workspace on " . config('app.name') . '.')
            ->line('Click the button below to set your password and get started.')
            ->action('Set Your Password', $resetUrl)
            ->line('This link expires in 60 minutes.')
            ->line('If you were not expecting this invitation, no further action is required.');
    }
}
