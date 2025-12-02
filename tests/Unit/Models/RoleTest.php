<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Role;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class RoleTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_can_create_a_role(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role = $team->addRole('admin', ['posts.view'], 'Administrator');

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('admin', $role->code);
        $this->assertEquals('Administrator', $role->name);
        $this->assertEquals($team->id, $role->team_id);
    }

    /**
     * @test
     * @throws Exception
     */
    public function role_can_have_multiple_permissions(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role = $team->addRole('editor', ['posts.view', 'posts.create', 'posts.edit'], 'Editor');

        $this->assertCount(3, $role->permissions);
        $this->assertTrue($role->permissions->pluck('code')->contains('posts.view'));
        $this->assertTrue($role->permissions->pluck('code')->contains('posts.create'));
        $this->assertTrue($role->permissions->pluck('code')->contains('posts.edit'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function role_can_be_retrieved_by_code(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $team->addRole('admin', ['posts.view'], 'Administrator');
        $role = $team->getRole('admin');

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('admin', $role->code);
    }

    /**
     * @test
     * @throws Exception
     */
    public function role_returns_null_for_invalid_code(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role = $team->getRole('nonexistent');

        $this->assertNull($role);
    }

    /**
     * @test
     * @throws Exception
     */
    public function role_belongs_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role = $team->addRole('admin', ['posts.view'], 'Administrator');

        $this->assertInstanceOf(Team::class, $role->team);
        $this->assertEquals($team->id, $role->team->id);
    }
}

