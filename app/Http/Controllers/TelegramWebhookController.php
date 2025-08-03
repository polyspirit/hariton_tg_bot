<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    private TelegramBotService $telegramService;
    private OpenAIService $openAIService;

    public function __construct(TelegramBotService $telegramService, OpenAIService $openAIService)
    {
        $this->telegramService = $telegramService;
        $this->openAIService = $openAIService;
    }

    /**
     * Handle incoming webhook from Telegram
     */
    public function handle(Request $request): Response
    {
        try {
            $update = $request->all();

            Log::info('Telegram webhook received', [
                'update_id' => $update['update_id'] ?? null,
                'type' => $this->getUpdateType($update),
            ]);

            // Handle different types of updates
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error handling Telegram webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'User';

        Log::info('Processing Telegram message', [
            'chat_id' => $chatId,
            'text' => $text,
            'user' => $firstName,
        ]);

        // Handle commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $firstName);
            return;
        }

        // Handle regular messages with AI
        if (!empty($text)) {
            $this->handleAIMessage($chatId, $text, $firstName);
        }
    }

    /**
     * Handle bot commands
     */
    private function handleCommand(int $chatId, string $text, string $firstName): void
    {
        $command = strtolower(trim($text));

        switch ($command) {
            case '/start':
                $welcomeMessage = "👋 Привет, {$firstName}!\n\nЯ AI-ассистент, готовый помочь вам с вопросами. Просто напишите мне, и я постараюсь ответить.\n\nИспользуйте /help для получения справки.";

                $this->telegramService->sendMessage($chatId, $welcomeMessage);
                break;

            case '/help':
                $helpMessage = "🤖 <b>Доступные команды:</b>\n\n" .
                    "/start - Начать работу с ботом\n" .
                    "/help - Показать эту справку\n" .
                    "/status - Проверить статус бота\n\n" .
                    "Просто напишите любой вопрос, и я постараюсь на него ответить!";

                $this->telegramService->sendMessage($chatId, $helpMessage);
                break;

            case '/status':
                $botInfo = $this->telegramService->getMe();
                $statusMessage = "✅ <b>Статус бота:</b>\n\n" .
                    "Имя: {$botInfo['first_name']}\n" .
                    "Username: @{$botInfo['username']}\n" .
                    "ID: {$botInfo['id']}\n\n" .
                    "Бот работает корректно!";

                $this->telegramService->sendMessage($chatId, $statusMessage);
                break;

            default:
                $unknownMessage = "❓ Неизвестная команда. Используйте /help для получения справки.";
                $this->telegramService->sendMessage($chatId, $unknownMessage);
                break;
        }
    }

    /**
     * Handle AI-powered message responses
     */
    private function handleAIMessage(int $chatId, string $text, string $firstName): void
    {
        try {
            // Send typing indicator
            $this->sendTypingAction($chatId);

            // Get AI response
            $response = $this->openAIService->ask($text);

            if ($response) {
                $this->telegramService->sendMessage($chatId, $response);
            } else {
                $errorMessage = "😔 Извините, не удалось получить ответ. Попробуйте переформулировать вопрос.";
                $this->telegramService->sendMessage($chatId, $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('Error processing AI message', [
                'chat_id' => $chatId,
                'text' => $text,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = "😔 Произошла ошибка при обработке вашего сообщения. Попробуйте позже.";
            $this->telegramService->sendMessage($chatId, $errorMessage);
        }
    }

    /**
     * Handle callback queries (inline keyboard buttons)
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'] ?? '';
        $firstName = $callbackQuery['from']['first_name'] ?? 'User';

        Log::info('Processing Telegram callback query', [
            'chat_id' => $chatId,
            'data' => $data,
            'user' => $firstName,
        ]);

        // Handle different callback data
        switch ($data) {
            case 'help':
                $helpMessage = "🤖 <b>Доступные команды:</b>\n\n" .
                    "/start - Начать работу с ботом\n" .
                    "/help - Показать эту справку\n" .
                    "/status - Проверить статус бота\n\n" .
                    "Просто напишите любой вопрос, и я постараюсь на него ответить!";

                $this->telegramService->sendMessage($chatId, $helpMessage);
                break;

            default:
                $unknownMessage = "❓ Неизвестная команда.";
                $this->telegramService->sendMessage($chatId, $unknownMessage);
                break;
        }
    }

    /**
     * Send typing action to show bot is processing
     */
    private function sendTypingAction(int $chatId): void
    {
        try {
            // Note: This would require direct API call as the SDK might not support it
            // For now, we'll just log it
            Log::info('Sending typing action', ['chat_id' => $chatId]);
        } catch (\Exception $e) {
            Log::error('Failed to send typing action', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get update type for logging
     */
    private function getUpdateType(array $update): string
    {
        if (isset($update['message'])) {
            return 'message';
        }

        if (isset($update['callback_query'])) {
            return 'callback_query';
        }

        if (isset($update['inline_query'])) {
            return 'inline_query';
        }

        return 'unknown';
    }
}
