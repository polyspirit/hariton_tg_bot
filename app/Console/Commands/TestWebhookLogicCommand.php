<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestWebhookLogicCommand extends Command
{
    protected $signature = 'telegram:test-webhook-logic';
    protected $description = 'Test webhook logic and command handling';

    public function handle()
    {
        $this->info('Testing webhook logic...');

        $testCases = [
            [
                'chat_id' => '123456789',
                'from_name' => 'TestUser',
                'text' => '/start',
                'description' => 'Test /start command'
            ],
            [
                'chat_id' => '987654321',
                'from_name' => 'AnotherUser',
                'text' => '/help',
                'description' => 'Test /help command'
            ],
            [
                'chat_id' => '555666777',
                'from_name' => 'NewUser',
                'text' => 'Hello world',
                'description' => 'Test regular message'
            ]
        ];

        foreach ($testCases as $testCase) {
            $this->line("\n--- Testing: {$testCase['description']} ---");
            $this->line("Chat ID: {$testCase['chat_id']}");
            $this->line("From: {$testCase['from_name']}");
            $this->line("Message: {$testCase['text']}");

            // Симулируем создание чата
            $this->simulateChatCreation($testCase['chat_id'], $testCase['from_name']);

            // Тестируем обработку команды
            $response = $this->handleCommand($testCase['text'], $testCase['from_name']);
            $this->line("Response: {$response}");
        }

        $this->info('\nWebhook logic testing completed!');
    }

    private function simulateChatCreation($chatId, $name)
    {
        try {
            $chat = \App\Models\TelegraphChat::where('chat_id', $chatId)->first();

            if (!$chat) {
                $bot = \App\Models\TelegraphBot::first();

                if ($bot) {
                    \App\Models\TelegraphChat::create([
                        'chat_id' => $chatId,
                        'name' => $name,
                        'telegraph_bot_id' => $bot->id,
                    ]);

                    $this->info("✓ Created new chat: {$name} ({$chatId})");
                } else {
                    $this->error("✗ No bot found in database");
                }
            } else {
                $this->info("✓ Chat already exists: {$name} ({$chatId})");
            }
        } catch (\Exception $e) {
            $this->error("✗ Error creating chat: " . $e->getMessage());
        }
    }

    private function handleCommand($text, $fromName)
    {
        // Убираем лишние пробелы и приводим к нижнему регистру
        $command = strtolower(trim($text));

        switch ($command) {
            case '/start':
                return "🎉 Привет, {$fromName}! Добро пожаловать в бота Hariton!\n\n" .
                       "Я готов помочь вам. Вот что я умею:\n" .
                       "• Отвечать на ваши сообщения\n" .
                       "• Обрабатывать команды\n\n" .
                       "Просто напишите мне что-нибудь!";

            case '/help':
                return "📚 Справка по командам:\n\n" .
                       "/start - Начать работу с ботом\n" .
                       "/help - Показать эту справку\n" .
                       "/info - Информация о боте\n\n" .
                       "Просто напишите сообщение, и я отвечу!";

            case '/info':
                return "ℹ️ Информация о боте:\n\n" .
                       "🤖 Имя: Hariton Bot\n" .
                       "👨‍💻 Разработчик: Hariton\n" .
                       "🔧 Версия: 1.0\n\n" .
                       "Бот работает на Laravel + Telegraph";

            default:
                return "Привет, {$fromName}! Вы написали: {$text}\n\n" .
                       "Используйте /help для просмотра доступных команд.";
        }
    }
}
