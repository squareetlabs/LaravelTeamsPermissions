<?php

namespace Squareetlabs\LaravelTeamsPermissions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of team members.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function index(Team $team): JsonResponse
    {
        $members = $team->users()->with('membership.role')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->membership->role->id ?? null,
                    'code' => $user->membership->role->code ?? null,
                    'name' => $user->membership->role->name ?? null,
                ],
            ];
        });

        return response()->json([
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ],
            'members' => $members,
            'owner' => [
                'id' => $team->owner->id,
                'name' => $team->owner->name,
                'email' => $team->owner->email,
            ],
        ]);
    }

    /**
     * Add a member to a team.
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
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $userModel = TeamsFacade::model('user');
        $member = $userModel::findOrFail($validated['user_id']);

        try {
            $team->addUser($member, $validated['role']);

            return response()->json([
                'message' => 'Member added successfully',
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a team member's role.
     *
     * @param Request $request
     * @param Team $team
     * @param int $userId
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, Team $team, int $userId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|string',
        ]);

        $userModel = TeamsFacade::model('user');
        $member = $userModel::findOrFail($userId);

        try {
            $team->updateUser($member, $validated['role']);

            return response()->json([
                'message' => 'Member role updated successfully',
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove a member from a team.
     *
     * @param Request $request
     * @param Team $team
     * @param int $userId
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request, Team $team, int $userId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->ownsTeam($team)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $userModel = TeamsFacade::model('user');
        $member = $userModel::findOrFail($userId);

        try {
            $team->deleteUser($member);

            return response()->json(['message' => 'Member removed successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

