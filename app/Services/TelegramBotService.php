<?php

namespace App\Services;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private Api $telegram;
    private string $token;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');

        if (empty($this->token)) {
            throw new \InvalidArgumentException('Telegram bot token is not configured');
        }

        $this->telegram = new Api($this->token);
    }

    /**
     * Send message to Telegram chat
     */
    public function sendMessage(int $chatId, string $text, array $options = []): bool
    {
        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'disable_web_page_preview' => $options['disable_web_page_preview'] ?? true,
            ]);

            Log::info('Telegram message sent successfully', [
                'chat_id' => $chatId,
                'message_id' => $response->getMessageId(),
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get bot information
     */
    public function getMe(): ?array
    {
        try {
            $response = $this->telegram->getMe();

            return [
                'id' => $response->getId(),
                'username' => $response->getUsername(),
                'first_name' => $response->getFirstName(),
                'can_join_groups' => $response->getCanJoinGroups(),
                'can_read_all_group_messages' => $response->getCanReadAllGroupMessages(),
                'supports_inline_queries' => $response->getSupportsInlineQueries(),
            ];
        } catch (TelegramSDKException $e) {
            Log::error('Failed to get bot information', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set webhook for bot
     */
    public function setWebhook(string $url, array $options = []): bool
    {
        try {
            $response = $this->telegram->setWebhook([
                'url' => $url,
                'allowed_updates' => $options['allowed_updates'] ?? ['message', 'callback_query'],
                'drop_pending_updates' => $options['drop_pending_updates'] ?? true,
            ]);

            Log::info('Telegram webhook set successfully', [
                'url' => $url,
                'result' => $response,
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to set Telegram webhook', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete webhook for bot
     */
    public function deleteWebhook(): bool
    {
        try {
            $response = $this->telegram->removeWebhook();

            Log::info('Telegram webhook deleted successfully', [
                'result' => $response,
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to delete Telegram webhook', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->telegram->getWebhookInfo();

            return [
                'url' => $response->getUrl(),
                'has_custom_certificate' => $response->getHasCustomCertificate(),
                'pending_update_count' => $response->getPendingUpdateCount(),
                'last_error_date' => $response->getLastErrorDate(),
                'last_error_message' => $response->getLastErrorMessage(),
                'max_connections' => $response->getMaxConnections(),
                'allowed_updates' => $response->getAllowedUpdates(),
            ];
        } catch (TelegramSDKException $e) {
            Log::error('Failed to get webhook info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send keyboard markup message
     */
    public function sendKeyboardMessage(int $chatId, string $text, array $keyboard, array $options = []): bool
    {
        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'reply_markup' => json_encode([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => $options['resize_keyboard'] ?? true,
                    'one_time_keyboard' => $options['one_time_keyboard'] ?? false,
                    'selective' => $options['selective'] ?? false,
                ]),
            ]);

            Log::info('Telegram keyboard message sent successfully', [
                'chat_id' => $chatId,
                'message_id' => $response->getMessageId(),
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send Telegram keyboard message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send inline keyboard message
     */
    public function sendInlineKeyboardMessage(
        int $chatId,
        string $text,
        array $inlineKeyboard,
        array $options = []
    ): bool {
        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $inlineKeyboard,
                ]),
            ]);

            Log::info('Telegram inline keyboard message sent successfully', [
                'chat_id' => $chatId,
                'message_id' => $response->getMessageId(),
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send Telegram inline keyboard message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
