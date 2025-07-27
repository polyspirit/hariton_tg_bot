<?php

namespace App\Console\Commands;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Console\Command;

class SetupTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:setup-webhook {url?}';
    protected $description = 'Setup Telegram webhook URL';

    public function handle()
    {
        $url = $this->argument('url') ?? config('app.url') . '/telegram/webhook';
        $token = config('telegraph.bots.default.token');

        $this->info("Setting up webhook URL: {$url}");

        try {
            // Используем HTTP запрос для установки webhook
            $apiUrl = "https://api.telegram.org/bot{$token}/setWebhook";
            $data = ['url' => $url];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($result && isset($result['ok']) && $result['ok']) {
                $this->info('Webhook setup successfully!');
                $this->info("Webhook URL: {$url}");
            } else {
                $this->error('Failed to setup webhook');
                if (isset($result['description'])) {
                    $this->error('Error: ' . $result['description']);
                }
            }
        } catch (\Exception $e) {
            $this->error('Error setting up webhook: ' . $e->getMessage());
        }
    }
}
