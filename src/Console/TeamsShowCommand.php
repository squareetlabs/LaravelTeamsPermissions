<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:show {team : The ID or name of the team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show details of a specific team';

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

        $this->info("Team Details:");
        $this->line("ID: {$team->id}");
        $this->line("Name: {$team->name}");
        $this->line("Owner: " . ($team->owner->email ?? 'N/A'));
        $this->line("Created: {$team->created_at->format('Y-m-d H:i:s')}");
        $this->newLine();

        $this->info("Members ({$team->users()->count()}):");
        $members = $team->users()->with('membership.role')->get();
        foreach ($members as $member) {
            $role = $member->membership->role->name ?? 'N/A';
            $this->line("  - {$member->email} ({$role})");
        }
        $this->newLine();

        $this->info("Roles ({$team->roles()->count()}):");
        $roles = $team->roles()->with('permissions')->get();
        foreach ($roles as $role) {
            $permissions = $role->permissions->pluck('code')->join(', ');
            $this->line("  - {$role->name} ({$role->code}): {$permissions}");
        }

        return self::SUCCESS;
    }
}

