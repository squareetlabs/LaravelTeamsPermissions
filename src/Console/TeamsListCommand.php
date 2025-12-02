<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all teams';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $teamModel = TeamsFacade::model('team');
        $teams = $teamModel::with('owner')->get();

        if ($teams->isEmpty()) {
            $this->info('No teams found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Owner', 'Members', 'Roles', 'Created At'],
            $teams->map(function ($team) {
                return [
                    $team->id,
                    $team->name,
                    $team->owner->email ?? 'N/A',
                    $team->users()->count(),
                    $team->roles()->count(),
                    $team->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }
}

