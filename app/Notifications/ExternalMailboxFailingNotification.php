<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\ExternalMailbox;

class ExternalMailboxFailingNotification extends Notification
{
    use Queueable;

    public $externalMailbox;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExternalMailbox $externalMailbox)
    {
        $this->externalMailbox = $externalMailbox;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action Required: External Mailbox Sync Failing')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We are having trouble syncing emails from one of your external mailboxes.')
            ->line('Mailbox: **' . $this->externalMailbox->email . '**')
            ->line('Status: **Failed 5 consecutive times.**')
            ->line('Error: ' . $this->externalMailbox->last_error)
            ->line('This usually happens if the password was changed or an App Password is required.')
            ->action('Update Credentials', url('/external-mailboxes/' . $this->externalMailbox->id . '/edit'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
