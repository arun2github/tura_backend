<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationMail extends Notification implements ShouldQueue
{
    use Queueable;

    public $email;
    public $token;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $email, string $token)
    {
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = "/api/verifyemail/".$this->token;
        
        return (new MailMessage)
            ->from(config('mail.from.address'), 'Tura Municipal Board')
            ->subject('Welcome to Tura Municipal Board - Verify Your Email')
            ->greeting('Welcome to Tura Municipal Board!')
            ->line('Dear ' . ($notifiable->firstname ?? 'User') . ',')
            ->line('Thank you for registering with Tura Municipal Board. We are excited to have you join our community.')
            ->line('To get started and access all our services, please verify your email address by clicking the button below:')
            ->action('Verify Email Address', url($url))
            ->line('**Why verify your email?**')
            ->line('• Access to job application portal')
            ->line('• Receive important notifications and updates')
            ->line('• Secure your account')
            ->line('• Complete your job applications')
            ->line('If you did not create this account, please ignore this email.')
            ->line('**Need help?** Contact us at ' . config('mail.from.address'))
            ->line('Thank you for choosing Tura Municipal Board!')
            ->salutation('Best regards,  
Tura Municipal Board  
Government of Meghalaya');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
