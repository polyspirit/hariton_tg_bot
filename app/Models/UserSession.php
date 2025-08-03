<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'state',
        'data',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get user associated with this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'telegram_user_id', 'telegram_user_id');
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get session data by key
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set session data
     */
    public function setData(string $key, mixed $value): void
    {
        $this->data = array_merge($this->data ?? [], [$key => $value]);
        $this->save();
    }

    /**
     * Clear session data
     */
    public function clearData(): void
    {
        $this->data = [];
        $this->state = null;
        $this->save();
    }
}
