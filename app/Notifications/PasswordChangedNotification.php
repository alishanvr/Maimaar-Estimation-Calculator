<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $password) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Password Has Been Changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account password has been changed by an administrator.')
            ->line('Your new password is: **'.$this->password.'**')
            ->line('Please log in and change your password as soon as possible.')
            ->action('Log In', config('app.url'))
            ->line('If you did not expect this change, please contact your administrator.');
    }
}
