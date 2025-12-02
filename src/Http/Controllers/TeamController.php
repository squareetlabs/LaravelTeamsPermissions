<?php

namespace Squareetlabs\LaravelTeamsPermissions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !method_exists($user, 'allTeams')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $teams = $user->allTeams()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'owner' => [
                    'id' => $team->owner->id,
                    'name' => $team->owner->name,
                    'email' => $team->owner->email,
                ],
                'members_count' => $team->users()->count(),
                'roles_count' => $team->roles()->count(),
                'created_at' => $team->created_at,
            ];
        });

        return response()->json($teams);
    }

    /**
     * Store a newly created team.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $team = Team::create([
            'name' => $validated['name'],
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Team created successfully',
            'team' => $team,
        ], 201);
    }

    /**
     * Display the specified team.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function show(Team $team): JsonResponse
    {
        $team->load(['owner', 'users', 'roles.permissions', 'groups.permissions']);

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'owner' => [
                'id' => $team->owner->id,
                'name' => $team->owner->name,
                'email' => $team->owner->email,
            ],
            'members' => $team->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->membership->role->name ?? null,
                ];
            }),
            'roles' => $team->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('code'),
                ];
            }),
            'created_at' => $team->created_at,
        ]);
    }

    /**
     * Update the specified team.
     *
     * @param Request $request
     * @param Team $team
     * @return JsonResponse
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $team->update($validated);

        return response()->json([
            'message' => 'Team updated successfully',
            'team' => $team,
        ]);
    }

    /**
     * Remove the specified team.
     *
     * @param Request $request
     * @param Team $team
     * @return JsonResponse
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }

    /**
     * Get all permissions for a team.
     *
     * @param Team $team
     * @return JsonResponse
     * @throws Exception
     */
    public function permissions(Team $team): JsonResponse
    {
        // Permissions are global, get them through roles and groups
        $permissions = $team->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->merge(
                $team->groups()
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn ($group) => $group->permissions)
            )
            ->unique('id')
            ->values();

        return response()->json([
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ],
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'code' => $permission->code,
                    'created_at' => $permission->created_at,
                ];
            }),
        ]);
    }
}

