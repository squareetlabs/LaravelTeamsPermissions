<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Ability;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class AbilityTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_can_create_an_ability(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $permission = TeamsFacade::model('permission')::create(['code' => 'posts.view', 'name' => 'View Posts']);

        $ability = TeamsFacade::model('ability')::create([
            'team_id' => $team->id,
            'permission_id' => $permission->id,
            'title' => 'View Post #1',
            'entity_id' => 1,
            'entity_type' => 'App\\Models\\Post',
        ]);

        $this->assertInstanceOf(Ability::class, $ability);
        $this->assertEquals($team->id, $ability->team_id);
        $this->assertEquals($permission->id, $ability->permission_id);
        $this->assertEquals('View Post #1', $ability->title);
    }

    /**
     * @test
     * @throws Exception
     */
    public function ability_belongs_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);
        $permission = TeamsFacade::model('permission')::create(['code' => 'posts.view', 'name' => 'View Posts']);

        $ability = TeamsFacade::model('ability')::create([
            'team_id' => $team->id,
            'permission_id' => $permission->id,
            'title' => 'View Post #1',
            'entity_id' => 1,
            'entity_type' => 'App\\Models\\Post',
        ]);

        $this->assertInstanceOf(Team::class, $ability->team);
        $this->assertEquals($team->id, $ability->team->id);
    }

    /**
     * @test
     * @throws Exception
     */
    public function ability_can_be_assigned_to_user(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $permission = TeamsFacade::model('permission')::create(['code' => 'posts.view', 'name' => 'View Posts']);

        $ability = TeamsFacade::model('ability')::create([
            'team_id' => $team->id,
            'permission_id' => $permission->id,
            'title' => 'View Post #1',
            'entity_id' => 1,
            'entity_type' => 'App\\Models\\Post',
        ]);

        $ability->users()->attach($user, ['forbidden' => false]);

        $this->assertTrue($user->abilities()->where('abilities.id', $ability->id)->exists());
    }
}

