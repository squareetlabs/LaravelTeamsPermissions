<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Models;

use Squareetlabs\LaravelTeamsPermissions\Models\Permission;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class PermissionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_permission(): void
    {
        $permission = TeamsFacade::model('permission')::create([
            'code' => 'posts.view',
            'name' => 'View Posts',
        ]);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('posts.view', $permission->code);
        $this->assertEquals('View Posts', $permission->name);
    }

    /**
     * @test
     * @throws Exception
     */
    public function permission_can_be_attached_to_role(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role = $team->addRole('admin', ['posts.view', 'posts.create'], 'Administrator');

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->pluck('code')->contains('posts.view'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function permission_can_be_attached_to_multiple_roles(): void
    {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $user->id]);

        $role1 = $team->addRole('admin', ['posts.view'], 'Administrator');
        $role2 = $team->addRole('editor', ['posts.view'], 'Editor');

        $permission = TeamsFacade::model('permission')::where('code', 'posts.view')->first();

        $this->assertCount(2, $permission->roles);
    }

    /**
     * @test
     */
    public function permission_code_must_be_unique(): void
    {
        TeamsFacade::model('permission')::create(['code' => 'posts.view', 'name' => 'View Posts']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TeamsFacade::model('permission')::create(['code' => 'posts.view', 'name' => 'View Posts Again']);
    }
}

