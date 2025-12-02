<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Feature;

use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class PermissionCheckingTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function user_can_check_team_permission(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view', 'posts.create'], 'Member');
        $team->addUser($member, 'member');

        $this->assertTrue($member->hasTeamPermission($team, 'posts.view'));
        $this->assertTrue($member->hasTeamPermission($team, 'posts.create'));
        $this->assertFalse($member->hasTeamPermission($team, 'posts.delete'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function owner_has_all_permissions(): void
    {
        $owner = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($owner->hasTeamPermission($team, 'any.permission'));
        $this->assertTrue($owner->hasTeamRole($team, 'any.role'));
    }
}

