<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteWebhookCommand extends Command
{
    protected $signature = 'telegram:delete-webhook';
    protected $description = 'Delete current webhook';

    public function handle()
    {
        $token = config('telegraph.bots.default.token');

        $this->info('Deleting webhook...');

        try {
            $apiUrl = "https://api.telegram.org/bot{$token}/deleteWebhook";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($result && isset($result['ok']) && $result['ok']) {
                $this->info('Webhook deleted successfully!');
            } else {
                $this->error('Failed to delete webhook');
                if (isset($result['description'])) {
                    $this->error('Error: ' . $result['description']);
                }
            }
        } catch (\Exception $e) {
            $this->error('Error deleting webhook: ' . $e->getMessage());
            return 1;
        }
    }
}
