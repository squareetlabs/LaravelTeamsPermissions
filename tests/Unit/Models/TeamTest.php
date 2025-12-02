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

    /**
     * @test
     * @throws Exception
     */
    public function it_can_add_a_group_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $group = $team->addGroup('developers', ['posts.view', 'posts.create'], 'Developers Group');

        $this->assertNotNull($group);
        $this->assertEquals('developers', $group->code);
        $this->assertEquals('Developers Group', $group->name);
        $this->assertCount(2, $group->permissions);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_remove_a_user_from_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');
        $team->refresh();
        $this->assertTrue($team->hasUser($member));

        $team->deleteUser($member);
        $team->refresh();
        $this->assertFalse($team->hasUser($member));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_update_user_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addRole('admin', ['posts.*'], 'Administrator');
        $team->addUser($member, 'member');

        $role = $team->getRole('admin');
        $team->users()->updateExistingPivot($member->id, ['role_id' => $role->id]);
        $team->refresh();
        $member->refresh();

        $this->assertEquals('admin', $member->teamRole($team)->code);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_get_all_users_including_owner(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member1, 'member');
        $team->addUser($member2, 'member');
        
        // Refresh to load relationships
        $team->refresh();
        $team->load('users', 'owner');

        $allUsers = $team->allUsers();

        $this->assertCount(3, $allUsers);
        $this->assertTrue($allUsers->contains($owner));
        $this->assertTrue($allUsers->contains($member1));
        $this->assertTrue($allUsers->contains($member2));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_prevents_adding_owner_as_member(): void
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Owner already belongs to the team.');
        $team->addUser($owner, 'member');
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_prevents_adding_duplicate_user(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');
        $team->refresh();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User already belongs to the team.');
        $team->addUser($member, 'member');
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_sync_permissions_to_role(): void
    {
        $user = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $role = $team->addRole('admin', ['posts.view'], 'Administrator');
        $this->assertCount(1, $role->permissions);

        // syncRolePermissions doesn't exist as a public method
        // Instead, we can manually sync permissions
        // First ensure permissions exist
        $permissionCodes = ['posts.view', 'posts.create', 'posts.edit'];
        foreach ($permissionCodes as $code) {
            TeamsFacade::model('permission')::firstOrCreate(['code' => $code], ['name' => ucfirst(str_replace('.', ' ', $code))]);
        }
        
        $permissionIds = TeamsFacade::model('permission')::whereIn('code', $permissionCodes)
            ->pluck('id')
            ->toArray();
        $role->permissions()->sync($permissionIds);
        $role->refresh();
        $role->load('permissions');

        $this->assertCount(3, $role->permissions);
    }
}

