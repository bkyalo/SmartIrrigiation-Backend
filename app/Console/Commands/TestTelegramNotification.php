<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\TelegramNotification;
use Illuminate\Support\Facades\Notification;

class TestTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {message? : The message to send} {--chat= : Optional chat ID to override default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $message = $this->argument('message') ?? 'This is a test notification from your Laravel application!';
        $chatId = $this->option('chat') ?: config('telegram.chat_id');

        if (empty($chatId)) {
            $this->error('Telegram chat ID is not configured');
            return 1;
        }

        $this->info("Sending test message to chat ID: {$chatId}");
        
        // Test direct HTTP request first
        $this->info("\nTesting direct HTTP request to Telegram API...");
        $directResponse = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot" . config('telegram.bot_token') . "/sendMessage", [
            'chat_id' => $chatId,
            'text' => 'Direct test: ' . $message
        ]);

        $this->info("Direct API Response: " . $directResponse->status());
        $this->info("Response Body: " . $directResponse->body());

        // Now test via Laravel notification
        $this->info("\nTesting Laravel notification...");
        try {
            $notification = new TelegramNotification('Laravel Notification: ' . $message, ['buttons' => true]);
            
            // Send using notification channel
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'debug' => true
            ])->post('https://api.telegram.org/bot' . config('telegram.bot_token') . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => 'Laravel Notification: ' . $message,
                'parse_mode' => 'HTML'
            ]);

            $this->info("Laravel Notification Response: " . $response->status());
            $this->info("Response Body: " . $response->body());
            
            if ($response->successful()) {
                $this->info("\n✅ Notification sent successfully via direct HTTP call!");
                
                // Try the notification channel again
                try {
                    $notification->toTelegram((object) ['telegram_chat_id' => $chatId]);
                    $this->info("✅ Notification channel also executed successfully!");
                } catch (\Exception $e) {
                    $this->error("❌ Notification channel error: " . $e->getMessage());
                    $this->line("But the direct HTTP call worked, so the issue is with the Laravel notification channel.");
                }
            } else {
                $this->error("❌ Failed to send notification: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->line("\nDebug Info:");
            $this->line("Bot Token: " . (!empty(config('telegram.bot_token')) ? '***' . substr(config('telegram.bot_token'), -4) : 'Not set'));
            $this->line("Chat ID: " . $chatId);
            return 1;
        }

        return 0;
    }
}
