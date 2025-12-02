<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Feature\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Squareetlabs\LaravelTeamsPermissions\Middleware\Permission;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class PermissionMiddlewareTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function middleware_allows_access_with_correct_permission(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        Auth::login($member);

        $request = Request::create('/test', 'GET');
        $request->merge(['team_id' => $team->id]);
        $request->setUserResolver(function () use ($member) {
            return $member;
        });

        $middleware = new Permission();
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        }, 'posts.view', (string) $team->id, false);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function middleware_denies_access_without_permission(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);
        $team->addRole('member', ['posts.view'], 'Member');
        $team->addUser($member, 'member');

        Auth::login($member);

        $request = Request::create('/test', 'GET');
        $request->merge(['team_id' => $team->id]);
        $request->setUserResolver(function () use ($member) {
            return $member;
        });

        $middleware = new Permission();
        
        // The middleware will abort with 403, so we need to catch the exception
        try {
            $response = $middleware->handle($request, function ($req) {
                return response('OK');
            }, 'posts.delete', (string) $team->id, false);
            $this->fail('Expected HttpException was not thrown');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function owner_has_access_to_all_permissions(): void
    {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'user_id' => $owner->id]);

        Auth::login($owner);

        $request = Request::create('/test', 'GET');
        $request->merge(['team_id' => $team->id]);
        $request->setUserResolver(function () use ($owner) {
            return $owner;
        });

        $middleware = new Permission();
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        }, 'any.permission', (string) $team->id, false);

        $this->assertEquals(200, $response->getStatusCode());
    }
}

