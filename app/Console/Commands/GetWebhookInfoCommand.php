<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetWebhookInfoCommand extends Command
{
    protected $signature = 'telegram:webhook-info';
    protected $description = 'Get current webhook information';

    public function handle()
    {
        $token = config('telegraph.bots.default.token');

        $this->info('Getting webhook information...');

        try {
            $apiUrl = "https://api.telegram.org/bot{$token}/getWebhookInfo";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($result && isset($result['ok']) && $result['ok']) {
                $webhookInfo = $result['result'];

                $this->info('Webhook Information:');
                $this->line("URL: " . ($webhookInfo['url'] ?? 'Not set'));
                $this->line("Has custom certificate: " . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
                $this->line("Pending update count: " . ($webhookInfo['pending_update_count'] ?? 0));
                $this->line("Last error date: " . ($webhookInfo['last_error_date'] ?? 'None'));
                $this->line("Last error message: " . ($webhookInfo['last_error_message'] ?? 'None'));
                $this->line("Max connections: " . ($webhookInfo['max_connections'] ?? 'Default'));
            } else {
                $this->error('Failed to get webhook information');
                if (isset($result['description'])) {
                    $this->error('Error: ' . $result['description']);
                }
            }
        } catch (\Exception $e) {
            $this->error('Error getting webhook information: ' . $e->getMessage());
            return 1;
        }
    }
}
