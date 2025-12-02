<?php

namespace Squareetlabs\LaravelTeamsPermissions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Exception;

class RoleController extends Controller
{
    /**
     * Display a listing of roles for a team.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function index(Team $team): JsonResponse
    {
        $roles = $team->roles()->with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'code' => $role->code,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('code'),
            ];
        });

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     *
     * @param Request $request
     * @param Team $team
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        try {
            $role = $team->addRole(
                $validated['code'],
                $validated['permissions'],
                $validated['name'] ?? null,
                $validated['description'] ?? null
            );

            return response()->json([
                'message' => 'Role created successfully',
                'role' => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('code'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update the specified role.
     *
     * @param Request $request
     * @param Team $team
     * @param int $roleId
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, Team $team, int $roleId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        try {
            $role = $team->updateRole(
                $roleId,
                $validated['permissions'],
                $validated['name'] ?? null,
                $validated['description'] ?? null
            );

            return response()->json([
                'message' => 'Role updated successfully',
                'role' => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('code'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified role.
     *
     * @param Request $request
     * @param Team $team
     * @param int $roleId
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request, Team $team, int $roleId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $team->deleteRole($roleId);

            return response()->json(['message' => 'Role deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

