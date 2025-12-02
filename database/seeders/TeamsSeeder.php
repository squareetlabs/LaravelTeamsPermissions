<?php

namespace Squareetlabs\LaravelTeamsPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $userModel = TeamsFacade::model('user');
        
        // Get or create a demo user
        $user = $userModel::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        // Create demo team
        $team = Team::firstOrCreate(
            ['name' => 'Demo Team'],
            ['user_id' => $user->id]
        );

        // Add roles
        if (!$team->hasRole('admin')) {
            $team->addRole('admin', ['*'], 'Administrator', 'Full access to all team resources');
        }

        if (!$team->hasRole('editor')) {
            $team->addRole('editor', [
                'posts.*',
                'comments.moderate',
            ], 'Editor', 'Can manage posts and moderate comments');
        }

        if (!$team->hasRole('viewer')) {
            $team->addRole('viewer', [
                'posts.view',
                'comments.view',
            ], 'Viewer', 'Can only view posts and comments');
        }

        $this->command->info('Demo team created successfully!');
    }
}

