<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:permissions {team : The ID or name of the team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all permissions for a team';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $teamIdentifier = $this->argument('team');
        $teamModel = TeamsFacade::model('team');
        
        $team = is_numeric($teamIdentifier)
            ? $teamModel::find($teamIdentifier)
            : $teamModel::where('name', $teamIdentifier)->first();

        if (!$team) {
            $this->error("Team not found: {$teamIdentifier}");
            return self::FAILURE;
        }

        // Permissions are global, get them through roles and groups
        $permissions = $team->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->merge(
                $team->groups()
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn ($group) => $group->permissions)
            )
            ->unique('id')
            ->values();

        if ($permissions->isEmpty()) {
            $this->info("No permissions found for team: {$team->name}");
            return self::SUCCESS;
        }

        $this->info("Permissions for team: {$team->name}");
        $this->table(
            ['ID', 'Code', 'Created At'],
            $permissions->map(function ($permission) {
                return [
                    $permission->id,
                    $permission->code,
                    $permission->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }
}

