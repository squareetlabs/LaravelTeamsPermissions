<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsImportPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:import-permissions {team : The ID or name of the team} {--file= : Path to the import file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import team permissions from a file';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $teamIdentifier = $this->argument('team');
        $file = $this->option('file');

        if (!$file || !File::exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $teamModel = TeamsFacade::model('team');
        $team = is_numeric($teamIdentifier)
            ? $teamModel::find($teamIdentifier)
            : $teamModel::where('name', $teamIdentifier)->first();

        if (!$team) {
            $this->error("Team not found: {$teamIdentifier}");
            return self::FAILURE;
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $content = File::get($file);

        if ($extension === 'json') {
            $data = json_decode($content, true);
        } elseif (in_array($extension, ['yaml', 'yml'])) {
            $data = yaml_parse($content);
        } else {
            $this->error("Unsupported file format: {$extension}");
            return self::FAILURE;
        }

        if (!$data || !isset($data['permissions']) || !isset($data['roles'])) {
            $this->error("Invalid file format. Expected 'permissions' and 'roles' keys.");
            return self::FAILURE;
        }

        $this->info("Importing permissions for team: {$team->name}");

        // Import permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permissionCode) {
                $team->getPermissionIds([$permissionCode]);
            }
            $this->line("Imported " . count($data['permissions']) . " permissions.");
        }

        // Import roles
        if (isset($data['roles']) && is_array($data['roles'])) {
            foreach ($data['roles'] as $roleData) {
                if (!isset($roleData['code']) || !isset($roleData['permissions'])) {
                    continue;
                }

                try {
                    if ($team->hasRole($roleData['code'])) {
                        $team->updateRole(
                            $roleData['code'],
                            $roleData['permissions'],
                            $roleData['name'] ?? null,
                            $roleData['description'] ?? null
                        );
                        $this->line("Updated role: {$roleData['code']}");
                    } else {
                        $team->addRole(
                            $roleData['code'],
                            $roleData['permissions'],
                            $roleData['name'] ?? null,
                            $roleData['description'] ?? null
                        );
                        $this->line("Created role: {$roleData['code']}");
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to import role {$roleData['code']}: {$e->getMessage()}");
                }
            }
        }

        // Import groups
        if (isset($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $groupData) {
                if (!isset($groupData['code'])) {
                    continue;
                }

                try {
                    if ($team->hasGroup($groupData['code'])) {
                        $team->updateGroup(
                            $groupData['code'],
                            $groupData['permissions'] ?? [],
                            $groupData['name'] ?? null
                        );
                        $this->line("Updated group: {$groupData['code']}");
                    } else {
                        $team->addGroup(
                            $groupData['code'],
                            $groupData['permissions'] ?? [],
                            $groupData['name'] ?? null
                        );
                        $this->line("Created group: {$groupData['code']}");
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to import group {$groupData['code']}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Import completed successfully!");

        return self::SUCCESS;
    }
}

