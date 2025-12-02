<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_can_create_a_team(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Test Team', $team->name);
        $this->assertEquals($user->id, $team->user_id);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_add_a_role_to_team(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $role = $team->addRole('admin', ['posts.view', 'posts.create'], 'Administrator');

        $this->assertNotNull($role);
        $this->assertEquals('admin', $role->code);
        $this->assertEquals('Administrator', $role->name);
        $this->assertCount(2, $role->permissions);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_add_a_user_to_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        // Refresh team to ensure relationships are loaded
        $team->refresh();
        $this->assertTrue($team->hasUser($member));
    }
}

