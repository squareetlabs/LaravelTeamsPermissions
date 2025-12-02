<?php

namespace Squareetlabs\LaravelTeamsPermissions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Exception;

class TeamGroupController extends Controller
{
    /**
     * Display a listing of groups for a team.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function index(Team $team): JsonResponse
    {
        $groups = $team->groups()->with('permissions')->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'code' => $group->code,
                'name' => $group->name,
                'permissions' => $group->permissions->pluck('code'),
            ];
        });

        return response()->json($groups);
    }

    /**
     * Store a newly created group.
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
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        try {
            $group = $team->addGroup(
                $validated['code'],
                $validated['permissions'],
                $validated['name'] ?? null
            );

            return response()->json([
                'message' => 'Group created successfully',
                'group' => [
                    'id' => $group->id,
                    'code' => $group->code,
                    'name' => $group->name,
                    'permissions' => $group->permissions->pluck('code'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update the specified group.
     *
     * @param Request $request
     * @param Team $team
     * @param int $groupId
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, Team $team, int $groupId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string',
        ]);

        try {
            $group = $team->updateGroup(
                $groupId,
                $validated['permissions'] ?? [],
                $validated['name'] ?? null
            );

            return response()->json([
                'message' => 'Group updated successfully',
                'group' => [
                    'id' => $group->id,
                    'code' => $group->code,
                    'name' => $group->name,
                    'permissions' => $group->permissions->pluck('code'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified group.
     *
     * @param Request $request
     * @param Team $team
     * @param int $groupId
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request, Team $team, int $groupId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $team->deleteGroup($groupId);

            return response()->json(['message' => 'Group deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

