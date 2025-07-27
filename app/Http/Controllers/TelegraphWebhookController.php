<?php

namespace App\Http\Controllers;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegraphWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            // Обрабатываем входящее сообщение
            $update = Telegraph::handleWebhook($request);

            if ($update) {
                $message = $update->message;

                if ($message) {
                    $chatId = $message->chat->id;
                    $text = $message->text ?? '';
                    $from = $message->from;
                    $fromName = $from ? $from->first_name : 'Unknown';

                    Log::info('Telegram message received', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'from' => $fromName
                    ]);

    // Создаем чат в базе данных, если его нет
                    $this->ensureChatExists($chatId, $fromName);

    // Обрабатываем команды
                    $response = $this->handleCommand($text, $fromName);

    // Отправляем ответ
                    Telegraph::message($chatId)
                    ->html($response)
                    ->send();
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error'], 500);
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

    private function ensureChatExists($chatId, $name)
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

                    Log::info("Created new chat in webhook: {$name} ({$chatId})");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error creating chat in webhook: " . $e->getMessage());
        }
    }
}
