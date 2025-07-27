<?php

namespace App\Console\Commands;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Console\Command;

class GetBotInfoCommand extends Command
{
    protected $signature = 'telegram:info';
    protected $description = 'Get information about the Telegram bot';

    public function handle()
    {
        $this->info('Getting bot information...');

        try {
            // Получаем информацию о боте из конфигурации
            $botToken = config('telegraph.bots.default.token');
            $botName = config('telegraph.bots.default.name');

            $this->info('Bot Information:');
            $this->line("Name: {$botName}");
            $this->line("Token: {$botToken}");
            $this->line("Token ID: " . explode(':', $botToken)[0]);

                        $this->info('Bot is configured correctly!');
        } catch (\Exception $e) {
            $this->error('Error getting bot information: ' . $e->getMessage());
            return 1;
        }
    }
}
