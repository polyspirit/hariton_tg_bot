<?php

namespace App\Console\Commands;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Console\Command;

class TestTelegramBotCommand extends Command
{
    protected $signature = 'telegram:test {chat_id} {message?}';
    protected $description = 'Test Telegram bot by sending a message';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $message = $this->argument('message') ?? 'Привет! Это тестовое сообщение от бота Hariton.';

        $this->info("Sending message to chat ID: {$chatId}");
        $this->info("Message: {$message}");

        try {
            $response = Telegraph::message($chatId)
                ->html($message)
                ->send();

            $this->info('Message sent successfully!');
        } catch (\Exception $e) {
            $this->error('Error sending message: ' . $e->getMessage());
            return 1;
        }
    }
}
