<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:bot 
                            {action : Action to perform (info, set-webhook, delete-webhook, webhook-info)}
                            {--url= : Webhook URL for set-webhook action}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Telegram bot operations';

    private TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            switch ($action) {
                case 'info':
                    return $this->getBotInfo();
                case 'set-webhook':
                    return $this->setWebhook();
                case 'delete-webhook':
                    return $this->deleteWebhook();
                case 'webhook-info':
                    return $this->getWebhookInfo();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info('Available actions: info, set-webhook, delete-webhook, webhook-info');
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            Log::error('Telegram bot command error', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Get bot information
     */
    private function getBotInfo(): int
    {
        $this->info('Getting bot information...');

        $botInfo = $this->telegramService->getMe();

        if (!$botInfo) {
            $this->error('Failed to get bot information');
            return 1;
        }

        $this->info('Bot Information:');
        $this->line("ID: {$botInfo['id']}");
        $this->line("Name: {$botInfo['first_name']}");
        $this->line("Username: @{$botInfo['username']}");
        $this->line("Can join groups: " . ($botInfo['can_join_groups'] ? 'Yes' : 'No'));
        $this->line("Can read all group messages: " . ($botInfo['can_read_all_group_messages'] ? 'Yes' : 'No'));
        $this->line("Supports inline queries: " . ($botInfo['supports_inline_queries'] ? 'Yes' : 'No'));

        return 0;
    }

    /**
     * Set webhook
     */
    private function setWebhook(): int
    {
        $url = $this->option('url');

        if (!$url) {
            $this->error('URL is required for set-webhook action');
            $this->info('Usage: php artisan telegram:bot set-webhook --url=https://yourdomain.com/telegram/webhook');
            return 1;
        }

        $this->info("Setting webhook to: {$url}");

        $success = $this->telegramService->setWebhook($url);

        if ($success) {
            $this->info('Webhook set successfully!');
            return 0;
        } else {
            $this->error('Failed to set webhook');
            return 1;
        }
    }

    /**
     * Delete webhook
     */
    private function deleteWebhook(): int
    {
        $this->info('Deleting webhook...');

        $success = $this->telegramService->deleteWebhook();

        if ($success) {
            $this->info('Webhook deleted successfully!');
            return 0;
        } else {
            $this->error('Failed to delete webhook');
            return 1;
        }
    }

    /**
     * Get webhook information
     */
    private function getWebhookInfo(): int
    {
        $this->info('Getting webhook information...');

        $webhookInfo = $this->telegramService->getWebhookInfo();

        if (!$webhookInfo) {
            $this->error('Failed to get webhook information');
            return 1;
        }

        $this->info('Webhook Information:');
        $this->line("URL: " . ($webhookInfo['url'] ?: 'Not set'));
        $this->line("Has custom certificate: " . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
        $this->line("Pending update count: {$webhookInfo['pending_update_count']}");
        $this->line("Max connections: {$webhookInfo['max_connections']}");

        if ($webhookInfo['last_error_date']) {
            $this->line("Last error date: " . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
        }

        if ($webhookInfo['last_error_message']) {
            $this->line("Last error message: {$webhookInfo['last_error_message']}");
        }

        if ($webhookInfo['allowed_updates']) {
            $this->line("Allowed updates: " . implode(', ', $webhookInfo['allowed_updates']));
        }

        return 0;
    }
}
