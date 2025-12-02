<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Traits;

use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class HasTeamsTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function user_can_check_if_they_own_a_team(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->ownsTeam($team));
    }

    /**
     * @test
     * @throws Exception
     */
    public function user_can_check_if_they_belong_to_a_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        $this->assertTrue($member->belongsToTeam($team));
    }

    /**
     * @test
     * @throws Exception
     */
    public function user_can_get_their_role_in_a_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        $role = $member->teamRole($team);
        $this->assertNotNull($role);
        $this->assertEquals('member', $role->code);
    }
}

