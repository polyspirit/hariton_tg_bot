<?php

namespace App\Console\Commands;

use App\Models\TelegraphBot;
use Illuminate\Console\Command;

class RegisterTelegramBotCommand extends Command
{
    protected $signature = 'telegram:register-bot {token?} {name?}';
    protected $description = 'Register Telegram bot in database';

    public function handle()
    {
        $token = $this->argument('token') ?? config('telegraph.bots.default.token');
        $name = $this->argument('name') ?? config('telegraph.bots.default.name');

        if (!$token) {
            $this->error('Bot token is required!');
            return 1;
        }

        try {
            // Проверяем, существует ли уже бот с таким токеном
            $existingBot = TelegraphBot::where('token', $token)->first();

            if ($existingBot) {
                $this->warn("Bot with token {$token} already exists!");
                return 0;
            }

            // Создаем нового бота
            $bot = TelegraphBot::create([
                'token' => $token,
                'name' => $name,
            ]);

            $this->info("Bot '{$name}' registered successfully!");
            $this->info("Bot ID: {$bot->id}");
            $this->info("Token: {$token}");
        } catch (\Exception $e) {
            $this->error('Error registering bot: ' . $e->getMessage());
            return 1;
        }
    }
}
