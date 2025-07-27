<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot as TelegraphBotModel;

class TelegraphBot extends TelegraphBotModel
{
    protected $fillable = [
        'token',
        'name',
    ];

    protected $casts = [
        'token' => 'string',
        'name' => 'string',
    ];
}
