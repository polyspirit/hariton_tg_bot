<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat as TelegraphChatModel;

class TelegraphChat extends TelegraphChatModel
{
    protected $fillable = [
        'chat_id',
        'name',
        'telegraph_bot_id',
    ];

    protected $casts = [
        'chat_id' => 'string',
        'name' => 'string',
        'telegraph_bot_id' => 'integer',
    ];
}
