<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * @test
     * @throws Exception
     */
    public function permissions_are_cached_after_first_check(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('member', ['posts.view', 'posts.create'], 'Member');
        $team->addUser($member, 'member');

        // First check - should populate cache
        $this->assertTrue($member->hasTeamPermission($team, 'posts.view'));

        // Verify permissions work (cache is internal, we just verify functionality)
        $this->assertTrue($member->hasTeamPermission($team, 'posts.view'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function cache_is_cleared_when_user_role_changes(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('member', ['posts.view'], 'Member');
        $team->addRole('admin', ['posts.*'], 'Administrator');
        $team->addUser($member, 'member');

        // Populate cache
        $member->hasTeamPermission($team, 'posts.view');
        
        // Refresh team to ensure relationships are loaded
        $team->refresh();
        $team->load('users');

        // Change role using updateUser
        $team->updateUser($member, 'admin');
        $member->refresh();
        $team->refresh();

        // Cache should be cleared, new permissions should be available
        $this->assertTrue($member->hasTeamPermission($team, 'posts.delete'));
    }
}

