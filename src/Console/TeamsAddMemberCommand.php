<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsAddMemberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:add-member {team : The ID or name of the team} {user : The ID or email of the user} {role : The role code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a member to a team';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $teamIdentifier = $this->argument('team');
        $userIdentifier = $this->argument('user');
        $roleCode = $this->argument('role');

        $teamModel = TeamsFacade::model('team');
        $userModel = TeamsFacade::model('user');
        
        $team = is_numeric($teamIdentifier)
            ? $teamModel::find($teamIdentifier)
            : $teamModel::where('name', $teamIdentifier)->first();

        if (!$team) {
            $this->error("Team not found: {$teamIdentifier}");
            return self::FAILURE;
        }

        $user = is_numeric($userIdentifier)
            ? $userModel::find($userIdentifier)
            : $userModel::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return self::FAILURE;
        }

        try {
            $team->addUser($user, $roleCode);
            $this->info("User {$user->email} added to team {$team->name} with role {$roleCode}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

