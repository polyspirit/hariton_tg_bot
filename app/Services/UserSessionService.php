<?php

namespace App\Services;

use App\Models\UserSession;
use Carbon\Carbon;

class UserSessionService
{
    /**
     * Get or create user session
     */
    public function getSession(int $telegramUserId): UserSession
    {
        $session = UserSession::where('telegram_user_id', $telegramUserId)->first();

        if (!$session) {
            $session = UserSession::create([
                'telegram_user_id' => $telegramUserId,
                'state' => null,
                'data' => [],
                'expires_at' => null,
            ]);
        }

        // Clear expired session
        if ($session->isExpired()) {
            $session->clearData();
        }

        return $session;
    }

    /**
     * Set session state
     */
    public function setState(int $telegramUserId, string $state, int $expiresInMinutes = 30): UserSession
    {
        $session = $this->getSession($telegramUserId);

        $session->update([
            'state' => $state,
            'expires_at' => Carbon::now()->addMinutes($expiresInMinutes),
        ]);

        return $session;
    }

    /**
     * Get current session state
     */
    public function getState(int $telegramUserId): ?string
    {
        $session = $this->getSession($telegramUserId);
        return $session->state;
    }

    /**
     * Clear session state
     */
    public function clearState(int $telegramUserId): void
    {
        $session = $this->getSession($telegramUserId);
        $session->clearData();
    }

    /**
     * Check if user is in specific state
     */
    public function isInState(int $telegramUserId, string $state): bool
    {
        return $this->getState($telegramUserId) === $state;
    }

    /**
     * Set session data
     */
    public function setData(int $telegramUserId, string $key, mixed $value): void
    {
        $session = $this->getSession($telegramUserId);
        $session->setData($key, $value);
    }

    /**
     * Get session data
     */
    public function getData(int $telegramUserId, string $key, mixed $default = null): mixed
    {
        $session = $this->getSession($telegramUserId);
        return $session->getData($key, $default);
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        return UserSession::where('expires_at', '<', Carbon::now())->delete();
    }
}
