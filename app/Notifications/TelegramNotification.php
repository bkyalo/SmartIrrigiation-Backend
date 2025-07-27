<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Notifications\Notification;

class TelegramNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $options;

    /**
     * Create a new notification instance.
     *
     * @param string $message
     * @param array $options
     */
    public function __construct($message, array $options = [])
    {
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    /**
     * Get the Telegram message representation of the notification.
     *
     * @param mixed $notifiable
     * @return \NotificationChannels\Telegram\TelegramMessage
     */
    public function toTelegram($notifiable)
    {
        $chatId = $notifiable->telegram_chat_id ?? config('telegram.chat_id');
        
        if (empty($chatId)) {
            throw new \RuntimeException('Telegram chat ID is not configured.');
        }
        
        $message = TelegramMessage::create()
            ->to($chatId)
            ->content($this->message);

        // Add optional parameters if provided
        if (isset($this->options['buttons'])) {
            $message->button('View Dashboard', url('/'));
        }

        return $message;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
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
