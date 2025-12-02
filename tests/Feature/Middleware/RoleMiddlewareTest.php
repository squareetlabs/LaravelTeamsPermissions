<?php

namespace Squareetlabs\LaravelTeamsPermissions\Tests\Feature\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Squareetlabs\LaravelTeamsPermissions\Middleware\Role;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;
use Squareetlabs\LaravelTeamsPermissions\Tests\Models\User;
use Exception;

class RoleMiddlewareTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function middleware_allows_access_with_correct_role(): void
    {
        $owner = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'user_id' => $owner->id,
        ]);

        $team->addRole('admin', ['*'], 'Administrator');

        // Owner has all roles automatically
        $this->assertTrue($owner->hasTeamRole($team, 'admin'));

        // Authenticate the user
        Auth::login($owner);

        $request = Request::create('/test', 'GET');
        $request->merge(['team_id' => $team->id]);
        $request->setUserResolver(function () use ($owner) {
            return $owner;
        });

        $middleware = new Role();
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        }, 'admin', (string) $team->id, false);

        $this->assertEquals(200, $response->getStatusCode());
    }
}

