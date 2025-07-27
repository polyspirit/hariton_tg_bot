<?php

namespace App\Console\Commands;

use App\Models\TelegraphChat;
use Illuminate\Console\Command;

class ListChatsCommand extends Command
{
    protected $signature = 'telegram:list-chats';
    protected $description = 'List all chats in database';

    public function handle()
    {
        $chats = TelegraphChat::with('bot')->get();

        if ($chats->isEmpty()) {
            $this->info('No chats found in database.');
            return;
        }

        $this->info('Chats in database:');
        $this->line('');

        $headers = ['ID', 'Chat ID', 'Name', 'Bot', 'Created At'];
        $rows = [];

        foreach ($chats as $chat) {
            $rows[] = [
                $chat->id,
                $chat->chat_id,
                $chat->name,
                $chat->bot ? $chat->bot->name : 'Unknown',
                $chat->created_at->format('Y-m-d H:i:s')
            ];
        }

        $this->table($headers, $rows);
    }
}
