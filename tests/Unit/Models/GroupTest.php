<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Group;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class GroupTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_can_create_a_group(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $group = $team->addGroup('developers', ['posts.view', 'posts.create'], 'Developers Group');

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('developers', $group->code);
        $this->assertEquals('Developers Group', $group->name);
        $this->assertEquals($team->id, $group->team_id);
    }

    /**
     * @test
     * @throws Exception
     */
    public function group_can_have_multiple_permissions(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $group = $team->addGroup('developers', ['posts.view', 'posts.create', 'posts.edit'], 'Developers');

        $this->assertCount(3, $group->permissions);
    }

    /**
     * @test
     * @throws Exception
     */
    public function group_can_be_retrieved_by_code(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $team->addGroup('developers', ['posts.view'], 'Developers');
        $group = $team->getGroup('developers');

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('developers', $group->code);
    }

    /**
     * @test
     * @throws Exception
     */
    public function group_belongs_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $group = $team->addGroup('developers', ['posts.view'], 'Developers');

        $this->assertInstanceOf(Team::class, $group->team);
        $this->assertEquals($team->id, $group->team->id);
    }

    /**
     * @test
     * @throws Exception
     */
    public function user_can_be_added_to_group(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);

        $group = $team->addGroup('developers', ['posts.view'], 'Developers');
        $group->users()->attach($user);

        $this->assertTrue($user->groups()->where('groups.id', $group->id)->exists());
    }
}

