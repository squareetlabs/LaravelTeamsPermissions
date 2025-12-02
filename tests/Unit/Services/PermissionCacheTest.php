<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Services\PermissionCache;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class PermissionCacheTest extends TestCase
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
    public function it_can_cache_permissions(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('admin', ['posts.view', 'posts.create'], 'Administrator');
        $team->addUser($member, 'admin');

        $cache = new PermissionCache();
        $permissions = $cache->remember("user_{$member->id}_team_{$team->id}_permissions", function () use ($member, $team) {
            return $member->teamPermissions($team);
        });

        $this->assertIsArray($permissions);
        $this->assertContains('posts.view', $permissions);
        $this->assertContains('posts.create', $permissions);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_returns_cached_permissions(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('admin', ['posts.view'], 'Administrator');
        $team->addUser($member, 'admin');

        $cache = new PermissionCache();
        $key = "user_{$member->id}_team_{$team->id}_permissions";
        $permissions1 = $cache->remember($key, function () use ($member, $team) {
            return $member->teamPermissions($team);
        });
        $permissions2 = $cache->remember($key, function () use ($member, $team) {
            return $member->teamPermissions($team);
        });

        $this->assertEquals($permissions1, $permissions2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_clear_user_cache(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('admin', ['posts.view'], 'Administrator');
        $team->addUser($member, 'admin');

        $cache = new PermissionCache();
        $key = "user_{$member->id}_team_{$team->id}_permissions";
        $cache->remember($key, function () use ($member, $team) {
            return $member->teamPermissions($team);
        });

        $cache->forget($key);

        // Cache should be cleared
        $this->assertNull($cache->get($key));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_clear_team_cache(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('admin', ['posts.view'], 'Administrator');
        $team->addUser($member, 'admin');

        $cache = new PermissionCache();
        $key = "user_{$member->id}_team_{$team->id}_permissions";
        $cache->remember($key, function () use ($member, $team) {
            return $member->teamPermissions($team);
        });

        $cache->flush();

        // Cache should be cleared
        $this->assertNull($cache->get($key));
    }

    /**
     * @test
     * @throws Exception
     */
    public function owner_permissions_are_cached_correctly(): void
    {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);

        $cache = new PermissionCache();
        $key = "user_{$owner->id}_team_{$team->id}_permissions";
        $permissions = $cache->remember($key, function () use ($owner, $team) {
            return $owner->teamPermissions($team);
        });

        // Owner should have all permissions (represented as ['*'])
        $this->assertIsArray($permissions);
        $this->assertEquals(['*'], $permissions);
    }
}

