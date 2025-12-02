<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Support\Services\PermissionCache;

class TeamsClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all team permissions cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cache = new PermissionCache();
        $cache->flush();

        $this->info('Team permissions cache cleared successfully!');
        return self::SUCCESS;
    }
}

