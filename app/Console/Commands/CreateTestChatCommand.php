<?php

namespace App\Console\Commands;

use App\Models\TelegraphChat;
use App\Models\TelegraphBot;
use Illuminate\Console\Command;

class CreateTestChatCommand extends Command
{
    protected $signature = 'telegram:create-chat {chat_id} {name?}';
    protected $description = 'Create a test chat in database';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $name = $this->argument('name') ?? 'Test Chat';

        try {
            // Получаем первого бота
            $bot = TelegraphBot::first();

            if (!$bot) {
                $this->error('No bot found in database. Please register a bot first.');
                return 1;
            }

            // Проверяем, существует ли уже чат
            $existingChat = TelegraphChat::where('chat_id', $chatId)->first();

            if ($existingChat) {
                $this->warn("Chat with ID {$chatId} already exists!");
                return 0;
            }

            // Создаем новый чат
            $chat = TelegraphChat::create([
                'chat_id' => $chatId,
                'name' => $name,
                'telegraph_bot_id' => $bot->id,
            ]);

            $this->info("Chat '{$name}' created successfully!");
            $this->info("Chat ID: {$chat->chat_id}");
            $this->info("Bot ID: {$chat->telegraph_bot_id}");
        } catch (\Exception $e) {
            $this->error('Error creating chat: ' . $e->getMessage());
            return 1;
        }
    }
}
