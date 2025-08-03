<?php

namespace App\Console\Commands;

use App\Services\UserSessionService;
use Illuminate\Console\Command;

class CleanUserSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:clean';

    /**
     * The console command description.
     */
    protected $description = 'Clean expired user sessions';

    private UserSessionService $sessionService;

    public function __construct(UserSessionService $sessionService)
    {
        parent::__construct();
        $this->sessionService = $sessionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning expired user sessions...');

        $deletedCount = $this->sessionService->cleanExpiredSessions();

        $this->info("Cleaned {$deletedCount} expired sessions.");

        return 0;
    }
}
