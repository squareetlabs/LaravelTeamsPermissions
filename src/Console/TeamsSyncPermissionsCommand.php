<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsSyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:sync-permissions {--team= : Sync permissions for a specific team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions from config file to database';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $permissions = Config::get('teams.default_permissions');
        
        if (empty($permissions)) {
            $this->warn('No default permissions found in config. Add "default_permissions" to config/teams.php');
            return self::SUCCESS;
        }

        $teamIdentifier = $this->option('team');
        $teamModel = TeamsFacade::model('team');
        $permissionModel = TeamsFacade::model('permission');

        if ($teamIdentifier) {
            $team = is_numeric($teamIdentifier)
                ? $teamModel::find($teamIdentifier)
                : $teamModel::where('name', $teamIdentifier)->first();

            if (!$team) {
                $this->error("Team not found: {$teamIdentifier}");
                return self::FAILURE;
            }

            $this->syncPermissionsForTeam($team, $permissions);
        } else {
            $teams = $teamModel::all();
            foreach ($teams as $team) {
                $this->syncPermissionsForTeam($team, $permissions);
            }
        }

        $this->info('Permissions synced successfully!');
        return self::SUCCESS;
    }

    /**
     * Sync permissions for a specific team.
     *
     * @param mixed $team
     * @param array $permissions
     * @return void
     * @throws Exception
     */
    protected function syncPermissionsForTeam($team, array $permissions): void
    {
        $permissionModel = TeamsFacade::model('permission');
        $teamIdField = Config::get('teams.foreign_keys.team_id');

        foreach ($permissions as $code) {
            $permissionModel::firstOrCreate([
                $teamIdField => $team->id,
                'code' => $code,
            ]);
        }

        $this->line("Synced permissions for team: {$team->name}");
    }
}

