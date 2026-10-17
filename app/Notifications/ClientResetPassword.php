<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class ClientResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('client.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
