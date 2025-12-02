<?php

namespace Squareetlabs\LaravelTeamsPermissions\Policies;

use Illuminate\Database\Eloquent\Model;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

/**
 * TeamPolicy Base Class
 * 
 * Abstract base class for team-based authorization policies.
 * Provides helper methods for checking team permissions, abilities, and roles.
 * 
 * Extend this class to create policies that integrate with the team permission system.
 * 
 * @package Squareetlabs\LaravelTeamsPermissions\Policies
 */
abstract class TeamPolicy
{
    /**
     * Check if user has team permission.
     * 
     * Verifies that the user has the specified permission in the given team.
     *
     * @param Model $user The user to check permissions for
     * @param Model $team The team context
     * @param string $permission The permission code to check
     * @return bool True if user has permission, false otherwise
     * @throws Exception If user model doesn't have HasTeams trait
     */
    protected function checkTeamPermission(Model $user, Model $team, string $permission): bool
    {
        if (!method_exists($user, 'hasTeamPermission')) {
            return false;
        }

        return $user->hasTeamPermission($team, $permission);
    }

    /**
     * Check if user has team ability.
     * 
     * Verifies that the user has the specified ability on the given model in the team context.
     *
     * @param Model $user The user to check abilities for
     * @param Model $team The team context
     * @param string $ability The ability/permission code to check
     * @param Model $model The entity/model to check ability for
     * @return bool True if user has ability, false otherwise
     * @throws Exception If user model doesn't have HasTeams trait
     */
    protected function checkTeamAbility(Model $user, Model $team, string $ability, Model $model): bool
    {
        if (!method_exists($user, 'hasTeamAbility')) {
            return false;
        }

        return $user->hasTeamAbility($team, $ability, $model);
    }

    /**
     * Check if user has team role.
     * 
     * Verifies that the user has the specified role(s) in the given team.
     *
     * @param Model $user The user to check roles for
     * @param Model $team The team context
     * @param string|array $roles Role code(s) to check (can be single role or array)
     * @return bool True if user has role(s), false otherwise
     * @throws Exception If user model doesn't have HasTeams trait
     */
    protected function checkTeamRole(Model $user, Model $team, string|array $roles): bool
    {
        if (!method_exists($user, 'hasTeamRole')) {
            return false;
        }

        return $user->hasTeamRole($team, $roles);
    }

    /**
     * Get team from model.
     * 
     * Attempts to extract the team relationship from a model.
     * Tries common relationship names: 'team', 'teams', 'belongsToTeam'.
     * Falls back to checking for 'team_id' attribute.
     *
     * @param Model $model The model to extract team from
     * @return Model|null The Team model or null if not found
     * @throws Exception If team model is not configured
     */
    protected function getTeamFromModel(Model $model): ?Model
    {
        // Try common team relationship names
        $teamRelations = ['team', 'teams', 'belongsToTeam'];
        
        foreach ($teamRelations as $relation) {
            if (method_exists($model, $relation)) {
                $team = $model->$relation;
                if ($team instanceof Model) {
                    return $team;
                }
            }
        }

        // Try to get team_id attribute
        if (isset($model->team_id)) {
            $teamModel = TeamsFacade::model('team');
            return $teamModel::find($model->team_id);
        }

        return null;
    }
}

