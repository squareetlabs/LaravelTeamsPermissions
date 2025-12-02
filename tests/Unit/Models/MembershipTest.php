<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class MembershipTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function membership_has_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        $membership = $team->users()->where('users.id', $member->id)->first()->membership;

        $this->assertNotNull($membership);
        $this->assertNotNull($membership->role);
        $this->assertEquals('member', $membership->role->code);
    }

    /**
     * @test
     * @throws Exception
     */
    public function membership_has_timestamps(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        $membership = $team->users()->where('users.id', $member->id)->first()->membership;

        $this->assertNotNull($membership->created_at);
        $this->assertNotNull($membership->updated_at);
    }
}

