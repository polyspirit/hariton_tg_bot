<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use App\Services\OpenAIService;
use App\Services\UserSessionService;
use App\Models\TelegramUser;
use App\Models\Question;
use App\Models\QuestionTopic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    private TelegramBotService $telegramService;
    private OpenAIService $openAIService;
    private UserSessionService $sessionService;

    public function __construct(
        TelegramBotService $telegramService,
        OpenAIService $openAIService,
        UserSessionService $sessionService
    ) {
        $this->telegramService = $telegramService;
        $this->openAIService = $openAIService;
        $this->sessionService = $sessionService;
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
        $userId = $message['from']['id'];
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'User';

        Log::info('Processing Telegram message', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'text' => $text,
            'user' => $firstName,
        ]);

        // Check if user exists in database and get their role
        $telegramUser = $this->getOrCreateTelegramUser($chatId, $userId, $firstName);

        // Handle commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $userId, $text, $firstName, $telegramUser);
            return;
        }

        // Handle states for /add command
        $userState = $this->sessionService->getState($userId);
        if ($userState === 'add_waiting_question') {
            $this->handleAddQuestionInput($chatId, $userId, $text, $firstName);
            return;
        }

        if ($userState === 'add_waiting_answer') {
            $this->handleAddAnswerInput($chatId, $userId, $text, $firstName);
            return;
        }

        // Handle regular messages with AI (any text without command is treated as a question)
        if (!empty($text)) {
            $this->handleAIMessage($chatId, $text, $firstName);
        }
    }

    /**
     * Handle bot commands
     */
    private function handleCommand(
        int $chatId,
        int $userId,
        string $text,
        string $firstName,
        TelegramUser $telegramUser
    ): void {
        $command = strtolower(trim($text));

        switch ($command) {
            case '/start':
                $welcomeMessage = "👋 Привет, {$firstName}!\n\nЯ AI-ассистент, готовый помочь вам с вопросами.\n\n" .
                    "💡 <b>Важно:</b> Любое сообщение без команды автоматически обрабатывается как вопрос " .
                    "коту Харитону!\n\nИспользуйте /help для получения справки.";

                $this->telegramService->sendMessage($chatId, $welcomeMessage);
                break;

            case '/help':
                $helpMessage = "🤖 <b>Доступные команды:</b>\n\n" .
                    "/start - Начать работу с ботом\n" .
                    "/help - Показать справку\n" .
                    "/ask - Задать вопрос коту Харитону\n" .
                    "💡 <b>Важно:</b> Любое сообщение без команды автоматически обрабатывается как вопрос " .
                    "коту Харитону!";

                // Add admin commands to help if user is admin
                if ($telegramUser->isAdmin()) {
                    $helpMessage .= "\n\n🔧 <b>Админские команды:</b>\n" .
                        "/add - Добавить новый вопрос с ответом";
                }

                $this->telegramService->sendMessage($chatId, $helpMessage);
                break;

            case '/ask':
                $this->handleAskCommand($chatId, $userId, $firstName);
                break;

            case '/status':
                $botInfo = $this->telegramService->getMe();
                $statusMessage = "✅ <b>Статус бота:</b>\n\n" .
                    "Имя: {$botInfo['first_name']}\n" .
                    "Username: @{$botInfo['username']}\n" .
                    "ID: {$botInfo['id']}\n\n" .
                    "📊 <b>Информация о чате:</b>\n" .
                    "Chat ID: {$chatId}\n" .
                    "User ID: {$userId}\n\n" .
                    "Бот работает корректно!";

                $this->telegramService->sendMessage($chatId, $statusMessage);
                break;

            case '/add':
                $this->handleAddCommand($chatId, $userId, $firstName, $telegramUser);
                break;

            default:
                $unknownMessage = "❓ Неизвестная команда. Используйте /help для получения справки.";
                $this->telegramService->sendMessage($chatId, $unknownMessage);
                break;
        }
    }

    /**
     * Handle /ask command
     */
    private function handleAskCommand(int $chatId, int $userId, string $firstName): void
    {
        $askMessage = "🐱 Привет, {$firstName}!\n\n" .
            "Я готов ответить на любой вопрос, касающийся видео с котом Харитоном, где он отвечает на вопросы!\n\n" .
            "Пожалуйста, напишите ваш вопрос:";

        $this->sessionService->setState($userId, 'waiting_for_question', 30);
        $this->telegramService->sendMessage($chatId, $askMessage);
    }

    /**
     * Handle question input when user is in waiting state
     */
    private function handleQuestionInput(int $chatId, int $userId, string $text, string $firstName): void
    {
        if (empty(trim($text))) {
            $this->telegramService->sendMessage($chatId, "❓ Пожалуйста, напишите ваш вопрос.");
            return;
        }

        // Clear the waiting state
        $this->sessionService->clearState($userId);

        // Send typing indicator
        $this->sendTypingAction($chatId);

        try {
            // Get AI response
            $response = $this->openAIService->ask($text);

            if ($response) {
                $formattedResponse = "🐱 <b>Ответ кота Харитона:</b>\n\n{$response}";
                $this->telegramService->sendMessage($chatId, $formattedResponse);
            } else {
                $errorMessage = "😔 Извините, не удалось получить ответ. Попробуйте переформулировать вопрос.";
                $this->telegramService->sendMessage($chatId, $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('Error processing question for /ask command', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'question' => $text,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = "😔 Произошла ошибка при обработке вашего вопроса. Попробуйте позже.";
            $this->telegramService->sendMessage($chatId, $errorMessage);
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
                    "/help - Показать справку\n" .
                    "/ask - Задать вопрос коту Харитону\n" .
                    "💡 <b>Важно:</b> Любое сообщение без команды автоматически обрабатывается как вопрос " .
                    "коту Харитону!";

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
     * Get or create Telegram user
     */
    private function getOrCreateTelegramUser(int $chatId, int $userId, string $firstName): TelegramUser
    {
        $telegramUser = TelegramUser::where('user_id', $userId)->first();

        if (!$telegramUser) {
            // Create new user with default role 'user'
            $telegramUser = TelegramUser::create([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'name' => $firstName,
                'role' => 'user',
            ]);

            Log::info('Created new Telegram user', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'name' => $firstName,
                'role' => 'user',
            ]);
        } else {
            // Update name if it has changed
            if ($telegramUser->name !== $firstName) {
                $telegramUser->update(['name' => $firstName]);
            }
        }

        return $telegramUser;
    }

    /**
     * Handle /add command (admin only)
     */
    private function handleAddCommand(int $chatId, int $userId, string $firstName, TelegramUser $telegramUser): void
    {
        // Check if user is admin
        if (!$telegramUser->isAdmin()) {
            $errorMessage = "❌ У вас нет прав для выполнения этой команды.";
            $this->telegramService->sendMessage($chatId, $errorMessage);
            return;
        }

        $addMessage = "🔧 <b>Добавление нового вопроса</b>\n\n" .
            "Пожалуйста, введите вопрос, который хотите добавить:";

        $this->sessionService->setState($userId, 'add_waiting_question', 30);
        $this->telegramService->sendMessage($chatId, $addMessage);
    }

    /**
     * Handle question input for /add command
     */
    private function handleAddQuestionInput(int $chatId, int $userId, string $text, string $firstName): void
    {
        if (empty(trim($text))) {
            $this->telegramService->sendMessage($chatId, "❓ Пожалуйста, введите вопрос.");
            return;
        }

        // Save question to session
        $this->sessionService->setData($userId, 'add_question', $text);

        // Change state to waiting for answer
        $this->sessionService->setState($userId, 'add_waiting_answer', 30);

        $answerMessage = "✅ <b>Вопрос сохранен:</b> {$text}\n\n" .
            "Теперь введите ответ (да/нет, 1/0, true/false, yes/no):";

        $this->telegramService->sendMessage($chatId, $answerMessage);
    }

    /**
     * Handle answer input for /add command
     */
    private function handleAddAnswerInput(int $chatId, int $userId, string $text, string $firstName): void
    {
        if (empty(trim($text))) {
            $this->telegramService->sendMessage($chatId, "❓ Пожалуйста, введите ответ.");
            return;
        }

        // Convert answer to boolean
        $answer = $this->convertToBoolean($text);
        if ($answer === null) {
            $errorMessage = "❌ Неверный формат ответа. Пожалуйста, используйте один из вариантов:\n" .
                "• да/нет\n• 1/0\n• true/false\n• yes/no";
            $this->telegramService->sendMessage($chatId, $errorMessage);
            return;
        }

        // Get question from session
        $question = $this->sessionService->getData($userId, 'add_question');
        if (!$question) {
            $this->sessionService->clearState($userId);
            $this->telegramService->sendMessage($chatId, "❌ Ошибка: вопрос не найден. Начните заново с команды /add");
            return;
        }

        try {
            // Find the most suitable topic
            $topic = $this->findBestTopic($question);

            // Create new question
            Question::create([
                'question' => $question,
                'answer' => $answer,
                'topic_id' => $topic ? $topic->id : null,
            ]);

            $successMessage = "✅ <b>Вопрос успешно добавлен!</b>\n\n" .
                "📝 <b>Вопрос:</b> {$question}\n" .
                "✅ <b>Ответ:</b> " . ($answer ? 'Да' : 'Нет') . "\n" .
                "🏷️ <b>Топик:</b> " . ($topic ? $topic->topic : 'Не определен');

            $this->sessionService->clearState($userId);
            $this->telegramService->sendMessage($chatId, $successMessage);
        } catch (\Exception $e) {
            Log::error('Error creating question', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'question' => $question,
                'answer' => $answer,
                'error' => $e->getMessage(),
            ]);

            $this->sessionService->clearState($userId);
            $errorMessage = "❌ Произошла ошибка при создании вопроса. Попробуйте позже.";
            $this->telegramService->sendMessage($chatId, $errorMessage);
        }
    }

    /**
     * Convert text answer to boolean value
     */
    private function convertToBoolean(string $text): ?bool
    {
        $text = strtolower(trim($text));

        $trueValues = ['да', 'yes', 'true', '1', 'давай', 'конечно', 'ага', 'угу'];
        $falseValues = ['нет', 'no', 'false', '0', 'не', 'неа', 'не-а'];

        if (in_array($text, $trueValues)) {
            return true;
        }

        if (in_array($text, $falseValues)) {
            return false;
        }

        return null;
    }

    /**
     * Find the best matching topic for a question
     */
    private function findBestTopic(string $question): ?QuestionTopic
    {
        $topics = QuestionTopic::all();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($topics as $topic) {
            $score = $this->calculateTopicScore($question, $topic->topic);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $topic;
            }
        }

        // Only return topic if score is above threshold
        return $bestScore > 0.3 ? $bestMatch : null;
    }

    /**
     * Calculate similarity score between question and topic
     */
    private function calculateTopicScore(string $question, string $topic): float
    {
        $question = mb_strtolower($question);
        $topic = mb_strtolower($topic);

        // Simple keyword matching
        $topicKeywords = explode(' ', $topic);
        $questionWords = explode(' ', $question);

        $matches = 0;
        foreach ($topicKeywords as $keyword) {
            foreach ($questionWords as $word) {
                if (str_contains($word, $keyword) || str_contains($keyword, $word)) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / count($topicKeywords);
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
