<?php

namespace Squareetlabs\LaravelTeamsPermissions\Console;

use Illuminate\Console\Command;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsExportPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:export-permissions {team : The ID or name of the team} {--format=json : Export format (json, yaml)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export team permissions to a file';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $teamIdentifier = $this->argument('team');
        $format = $this->option('format');

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

        $roles = $team->roles()->with('permissions')->get()->map(function ($role) {
            return [
                'code' => $role->code,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('code')->toArray(),
            ];
        });

        $groups = $team->groups()->with('permissions')->get()->map(function ($group) {
            return [
                'code' => $group->code,
                'name' => $group->name,
                'permissions' => $group->permissions->pluck('code')->toArray(),
            ];
        });

        $data = [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ],
            'permissions' => $permissions->pluck('code')->toArray(),
            'roles' => $roles->toArray(),
            'groups' => $groups->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = "team_{$team->id}_permissions_" . now()->format('Y-m-d_His') . ".{$format}";

        if ($format === 'json') {
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'yaml') {
            $content = yaml_emit($data);
        } else {
            $this->error("Unsupported format: {$format}");
            return self::FAILURE;
        }

        file_put_contents($filename, $content);

        $this->info("Permissions exported to: {$filename}");
        $this->line("Exported {$permissions->count()} permissions, {$roles->count()} roles, and {$groups->count()} groups.");

        return self::SUCCESS;
    }
}

