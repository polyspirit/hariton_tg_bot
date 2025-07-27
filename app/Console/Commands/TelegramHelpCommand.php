<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TelegramHelpCommand extends Command
{
    protected $signature = 'telegram:help';
    protected $description = 'Show help for Telegram bot commands';

    public function handle()
    {
        $this->info('Telegram Bot Commands:');
        $this->line('');

        $this->line('1. Register bot in database:');
        $this->line('   php artisan telegram:register-bot');
        $this->line('');

        $this->line('2. Get bot information:');
        $this->line('   php artisan telegram:info');
        $this->line('');

        $this->line('3. Test sending message:');
        $this->line('   php artisan telegram:test {chat_id} {message}');
        $this->line('   Example: php artisan telegram:test 123456789 "Hello!"');
        $this->line('');

        $this->line('4. Create test chat:');
        $this->line('   php artisan telegram:create-chat {chat_id} {name}');
        $this->line('   Example: php artisan telegram:create-chat 123456789 "Test Chat"');
        $this->line('');

                $this->line('5. Get webhook information:');
        $this->line('   php artisan telegram:webhook-info');
        $this->line('');

                $this->line('6. Setup webhook (requires HTTPS):');
        $this->line('   php artisan telegram:setup-webhook {url}');
        $this->line('   Example: php artisan telegram:setup-webhook https://yourdomain.com/telegram/webhook');
        $this->line('');

        $this->line('7. Delete webhook:');
        $this->line('   php artisan telegram:delete-webhook');
        $this->line('');

        $this->info('Note: For webhook to work, you need a public HTTPS URL.');
        $this->info('Webhook will automatically create chat records in database when users send /start command.');
    }
}
