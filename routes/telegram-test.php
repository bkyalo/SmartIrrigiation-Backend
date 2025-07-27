<?php

use Illuminate\Support\Facades\Route;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Support\Facades\Notification;

Route::get('/test-telegram', function () {
    try {
        $response = Http::post('https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/getMe');
        
        if ($response->successful()) {
            return [
                'bot_info' => $response->json(),
                'config' => [
                    'bot_token' => config('services.telegram.bot_token'),
                    'chat_id' => config('services.telegram.chat_id'),
                ]
            ];
        }
        
        return [
            'error' => 'Failed to connect to Telegram API',
            'response' => $response->body(),
            'status' => $response->status()
        ];
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
});
