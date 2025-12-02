<?php

namespace Squareetlabs\LaravelTeamsPermissions\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;
use Squareetlabs\LaravelTeamsPermissions\Enums\AccessLevel;
use Squareetlabs\LaravelTeamsPermissions\Models\Owner;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Squareetlabs\LaravelTeamsPermissions\Support\Services\PermissionCache;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Exception;

/**
 * HasTeams Trait
 * 
 * This trait provides team-related functionality to User models.
 * It includes methods for checking permissions, roles, abilities, and managing team relationships.
 * 
 * @package Squareetlabs\LaravelTeamsPermissions\Traits
 */
trait HasTeams
{
    /**
     * Cache for permission decisions made during request lifecycle.
     *
     * @var array<string, bool>
     */
    private array $decisionCache = [];

    /**
     * Check if the user owns the given team.
     * 
     * The owner of a team has all permissions by default.
     *
     * @param object $team The team to check ownership for
     * @return bool True if user owns the team, false otherwise
     */
    public function ownsTeam(object $team): bool
    {
        return $this->id === $team->{$this->getForeignKey()};
    }

    /**
     * Retrieve all teams the user owns or belongs to.
     * 
     * Returns a merged collection of owned teams and teams the user is a member of,
     * sorted alphabetically by team name.
     *
     * @return Collection Collection of Team models
     */
    public function allTeams(): Collection
    {
        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    /**
     * Retrieve all teams the user owns.
     * 
     * Returns a relationship for teams where the user is the owner.
     *
     * @return HasMany Relationship to Team models
     * @throws Exception If team model is not configured
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(TeamsFacade::model('team'))->withoutGlobalScopes();
    }


    /**
     * Retrieve all teams the user belongs to (excluding owned teams).
     * 
     * Returns a relationship for teams where the user is a member.
     * Includes pivot data with role_id and timestamps.
     *
     * @return BelongsToMany Relationship to Team models with membership pivot
     * @throws Exception If team or membership models are not configured
     */
    public function teams(): BelongsToMany
    {
        return $this
            ->belongsToMany(TeamsFacade::model('team'), TeamsFacade::model('membership'), 'user_id', Config::get('teams.foreign_keys.team_id'))
            ->withoutGlobalScopes()
            ->withPivot('role_id')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Scope a query to eager load team permissions and related data.
     * 
     * This scope optimizes queries by eager loading:
     * - Teams with their roles and permissions
     * - Teams with their groups and permissions
     * - Teams with their abilities
     * - Owned teams with their roles, groups, and permissions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance
     * @return \Illuminate\Database\Eloquent\Builder The query builder with eager loading
     */
    public function scopeWithTeamPermissions($query)
    {
        return $query->with([
            'teams.roles.permissions',
            'teams.groups.permissions',
            'teams.abilities',
            'ownedTeams.roles.permissions',
            'ownedTeams.groups.permissions',
        ]);
    }

    /**
     * Retrieve abilities related to the user.
     *
     * @return MorphToMany
     * @throws Exception
     */
    public function abilities(): MorphToMany
    {
        return $this->morphToMany(TeamsFacade::model('ability'), 'entity', 'entity_ability')
            ->withPivot('forbidden')
            ->withTimestamps();
    }

    /**
     * Retrieve all groups the user belongs to.
     *
     * @return BelongsToMany
     * @throws Exception
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TeamsFacade::model('group'), 'group_user', 'user_id', 'group_id');
    }

    /**
     * Check if the user belongs to the specified team.
     *
     * @param object $team
     * @return bool
     * @throws Exception
     */
    public function belongsToTeam(object $team): bool
    {
        $teamIdField = Config::get('teams.foreign_keys.team_id');
        return $this->ownsTeam($team) || $this->teams()->where($teamIdField, $team->id)->exists();
    }

    /**
     * Retrieve the user's role in a team.
     *
     * @param object $team
     * @return mixed
     * @throws Exception
     */
    public function teamRole(object $team): mixed
    {
        if ($this->ownsTeam($team)) {
            return new Owner();
        }

        return $this->belongsToTeam($team)
            ? $team->getRole($this->teams()->find($team->id)?->membership?->role_id)
            : null;
    }


    /**
     * Check if the user has the specified role(s) on the team.
     * 
     * Team owners automatically have all roles.
     * 
     * @param object $team The team to check
     * @param string|array $roles Role code(s) to check (can be single role or array)
     * @param bool $require If true, all roles must match. If false, at least one must match
     * @return bool True if user has the required role(s), false otherwise
     * @throws Exception If team model is not configured
     */
    public function hasTeamRole(object $team, string|array $roles, bool $require = false): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        $userRole = $this->teamRole($team)?->code;

        $roles = (array) $roles;

        return $require
            ? !array_diff($roles, [$userRole])
            : in_array($userRole, $roles, true);
    }

    /**
     * Get the user's permissions for the given team.
     * 
     * Returns an array of permission codes the user has for the team.
     * Team owners automatically have ['*'] permission.
     * 
     * Permissions are cached for performance when caching is enabled.
     *
     * @param object $team The team to get permissions for
     * @param string|null $scope Scope to filter permissions: 'role' (role permissions only), 'group' (group permissions only), or null (all permissions)
     * @return array Array of permission codes
     * @throws Exception If team model is not configured
     */
    public function teamPermissions(object $team, string|null $scope = null): array
    {
        if ($this->ownsTeam($team)) {
            return ['*'];
        }

        $cache = new PermissionCache();
        $cacheKey = "user_{$this->id}_team_{$team->id}_permissions_" . ($scope ?? 'all');

        return $cache->remember($cacheKey, function () use ($team, $scope) {
            $permissions = [];

            if (!$scope || $scope === 'role') {
                $role = $this->teamRole($team);
                $permissions = array_merge($permissions, $role?->permissions?->pluck('code')?->toArray() ?? []);
            }

            if (!$scope || $scope === 'group') {
                $teamIdField = Config::get('teams.foreign_keys.team_id');
                $groupPermissions = $this->groups()->where($teamIdField, $team->id)
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn ($group) => $group->permissions->pluck('code'))
                    ->toArray();
                $permissions = array_merge($permissions, $groupPermissions);
            }

            return array_unique($permissions);
        });
    }

    /**
     * Determine if the user has the given permission(s) on the given team.
     * 
     * Team owners automatically have all permissions.
     * 
     * Uses caching when enabled for better performance.
     * Supports request lifecycle caching and persistent caching.
     *
     * @param object $team The team to check permissions for
     * @param string|array $permissions Permission code(s) to check (can be single permission or array)
     * @param bool $require If true, all permissions must match. If false, at least one must match
     * @param string|null $scope Scope to check: 'role' (role permissions only), 'group' (group permissions only), or null (all permissions)
     * @return bool True if user has the required permission(s), false otherwise
     * @throws Exception If team model is not configured
     */
    public function hasTeamPermission(object $team, string|array $permissions, bool $require = false, string|null $scope = null): bool
    {
        // Check to see if the user has enabled request lifecycle caching
        if (Config::get('teams.request.cache_decisions')) {
            // Serialize the data
            $serializedData = serialize([
                $this->attributes[$this->primaryKey],
                $team->attributes['id'],
                $permissions,
                $require,
                $scope
            ]);

            // Create a unique cache key for this request
            $cacheKey = hash('sha256', $serializedData);

            // Check to see if the cache key exists, if not populate it
            if (!isset($this->decisionCache[$cacheKey])) {
                $this->decisionCache[$cacheKey] = $this->determineTeamPermission($team, $permissions, $require, $scope);
            }

            // Return the cached decision
            return $this->decisionCache[$cacheKey];
        }

        // Use persistent cache
        $cache = new PermissionCache();
        $cacheKey = "user_{$this->id}_team_{$team->id}_permission_" . md5(serialize([$permissions, $require, $scope]));

        return $cache->remember($cacheKey, function () use ($team, $permissions, $require, $scope) {
            return $this->determineTeamPermission($team, $permissions, $require, $scope);
        });
    }

    /**
     * Determine if the user has the given permission on the given team.
     *
     * $require = true (all permissions in the array are required)
     * $require = false (only one or more permission in the array are required or $permissions is empty)
     *
     * @param object $team
     * @param string|array $permissions
     * @param bool $require
     * @param string|null $scope Scope of permissions to check (ex. 'role', 'group'), by default checking all permissions
     * @return bool
     * @throws Exception
     */
    protected function determineTeamPermission(object $team, string|array $permissions, bool $require = false, string|null $scope = null): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        $permissions = (array) $permissions;

        if (empty($permissions)) {
            return false;
        }

        // Optimized query using whereHas for better performance
        if (!$scope || $scope === 'role') {
            $hasPermissionViaRole = $this->teams()
                ->where('teams.id', $team->id)
                ->whereHas('roles.permissions', function ($query) use ($permissions) {
                    $permissionCodes = [];
                    foreach ($permissions as $permission) {
                        $segments = explode('.', $permission);
                        for ($i = 0; $i < count($segments); $i++) {
                            $code = implode('.', array_slice($segments, 0, $i + 1));
                            if ($i < count($segments) - 1) {
                                $code .= '.*';
                            }
                            $permissionCodes[] = $code;
                        }
                    }
                    $query->whereIn('code', array_unique($permissionCodes));
                })
                ->exists();

            if ($hasPermissionViaRole && !$require) {
                return true;
            }

            if (!$hasPermissionViaRole && $require) {
                return false;
            }
        }

        // Fallback to original method for groups or when scope is 'group'
        if (!$scope || $scope === 'group') {
            $userPermissions = $this->teamPermissions($team, $scope);

            foreach ($permissions as $permission) {
                $hasPermission = $this->checkPermissionWildcard($userPermissions, $permission);

                if ($hasPermission && !$require) {
                    return true;
                }

                if (!$hasPermission && $require) {
                    return false;
                }
            }
        }

        return $require;
    }

    /**
     * Get all ability that specific entity within team
     *
     * @param object $team
     * @param object $entity
     * @param bool $forbidden
     * @return mixed
     * @throws Exception
     */
    public function teamAbilities(object $team, object $entity, bool $forbidden = false): mixed
    {
        $teamIdField = Config::get('teams.foreign_keys.team_id');
        // Start building the query to retrieve abilities
        $abilities = $this->abilities()->where([
            $teamIdField => $team->id,
            'abilities.entity_id' => $entity->id,
            'abilities.entity_type' => $entity::class
        ]);

        // If filtering by forbidden abilities, add the condition
        if ($forbidden) {
            $abilities->wherePivot('forbidden', true);
        }

        // Retrieve the abilities
        return $abilities->get();
    }

    /**
     * Determinate if user has global groups permissions
     *
     * This function is to verify permissions within a universal group.
     * Especially in cases where a team requires a group enabling user additions
     * and removals without direct affiliation with the team.
     *
     * Example: Each team should have a global group of moderators.
     *
     * @param string $ability
     * @return bool
     */
    private function hasGlobalGroupPermissions(string $ability): bool
    {
        $teamIdField = Config::get('teams.foreign_keys.team_id');
        $permissions = $this->groups->whereNull($teamIdField)
            ->load('permissions')
            ->flatMap(fn ($group) => $group->permissions->pluck('code'))
            ->toArray();

        return $this->checkPermissionWildcard($permissions, $ability);
    }

    /**
     * Determine if user can perform an action on a specific entity.
     * 
     * This method checks abilities which are entity-specific permissions.
     * It uses an access level system where allowed and forbidden levels are compared.
     * 
     * Team owners and entity owners automatically have access.
     *
     * @param object $team The team context
     * @param string $permission The permission code to check
     * @param object $action_entity The entity/model to check ability for
     * @return bool True if user has the ability, false otherwise
     * @throws Exception If team or ability models are not configured
     */
    public function hasTeamAbility(object $team, string $permission, object $action_entity): bool
    {
        if ($this->ownsTeam($team) || (method_exists($action_entity, 'isOwner') && $action_entity->isOwner($this))) {
            return true;
        }

        $allowed = AccessLevel::DEFAULT->value;
        $forbidden = AccessLevel::FORBIDDEN->value;

        if ($this->hasTeamPermission($team, $permission, scope: 'role')) {
            $allowed = max($allowed, AccessLevel::ROLE_ALLOWED->value);
        }

        if ($this->hasTeamPermission($team, $permission, scope: 'group')) {
            $allowed = max($allowed, AccessLevel::GROUP_ALLOWED->value);
        }

        if ($this->hasGlobalGroupPermissions($permission)) {
            $allowed = max($allowed, AccessLevel::GLOBAL_ALLOWED->value);
        }

        $segments = collect(explode('.', $permission));

        $codes = $segments->map(function ($item, $key) use ($segments) {
            return $segments->take($key + 1)->implode('.') . ($key + 1 === $segments->count() ? '' : '.*') ;
        });

        $teamIdField = Config::get('teams.foreign_keys.team_id');
        $permission_ids = TeamsFacade::model('permission')::query()
            ->where($teamIdField, $team->id)
            ->whereIn('code', $codes)
            ->pluck('id')
            ->all();

        $role = $this->teamRole($team)->load(['abilities' => function ($query) use ($action_entity, $permission_ids) {
            $query->where([
                'abilities.entity_id' => $action_entity->id,
                'abilities.entity_type' => get_class($action_entity),
            ])->whereIn('permission_id', $permission_ids);
        }]);

        $teamIdField = Config::get('teams.foreign_keys.team_id');
        $groups = $this->groups->where($teamIdField, $team->id)->load(['abilities' => function ($query) use ($action_entity, $permission_ids) {
            $query->where([
                'abilities.entity_id' => $action_entity->id,
                'abilities.entity_type' => get_class($action_entity),
            ])->whereIn('permission_id', $permission_ids);
        }]);

        $this->load(['abilities' => function ($query) use ($action_entity, $permission_ids) {
            $query->where([
                'abilities.entity_id' => $action_entity->id,
                'abilities.entity_type' => get_class($action_entity),
            ])->whereIn('permission_id', $permission_ids);
        }]);

        foreach ([$role, ...$groups, $this] as $entity) {

            foreach ($entity->abilities as $ability) {

                if ($ability->pivot->forbidden) {
                    $forbidden = max($forbidden, $entity::class === TeamsFacade::model('role') ? AccessLevel::ROLE_FORBIDDEN->value : ($entity::class === TeamsFacade::model('group') ? AccessLevel::GROUP_FORBIDDEN->value : AccessLevel::USER_FORBIDDEN->value));
                } else {
                    $allowed = max($allowed, $entity::class === TeamsFacade::model('role') ? AccessLevel::ROLE_ALLOWED->value : ($entity::class === TeamsFacade::model('group') ? AccessLevel::GROUP_ALLOWED->value : AccessLevel::USER_ALLOWED->value));
                }
            }
        }

        return $allowed >= $forbidden;
    }


    /**
     * Allow user to perform an ability on entity
     *
     * @param object $team
     * @param string $permission
     * @param object $action_entity
     * @param object|null $target_entity
     * @return void
     * @throws Exception
     */
    public function allowTeamAbility(object $team, string $permission, object $action_entity, object|null $target_entity = null): void
    {
        $this->updateAbilityOnEntity($team, 'syncWithoutDetaching', $permission, $action_entity, $target_entity);
    }

    /**
     * Forbid user to perform an ability on entity
     *
     * @param object $team
     * @param string $permission
     * @param object $action_entity
     * @param object|null $target_entity
     * @return void
     * @throws Exception
     */
    public function forbidTeamAbility(object $team, string $permission, object $action_entity, object|null $target_entity = null): void
    {
        $this->updateAbilityOnEntity($team, 'syncWithoutDetaching', $permission, $action_entity, $target_entity, true);
    }

    /**
     * Delete user ability on entity
     *
     * @param object $team
     * @param string $permission
     * @param object $action_entity
     * @param object|null $target_entity
     * @return void
     * @throws Exception
     */
    public function deleteTeamAbility(object $team, string $permission, object $action_entity, object|null $target_entity = null): void
    {
        $this->updateAbilityOnEntity($team, 'detach', $permission, $action_entity, $target_entity);
    }

    /**
     * Helper method for attaching or detaching ability to entity
     *
     * @param object $team
     * @param string $method
     * @param string $permission
     * @param object $action_entity
     * @param object|null $target_entity
     * @param bool $forbidden
     * @return void
     * @throws Exception
     */
    private function updateAbilityOnEntity(object $team, string $method, string $permission, object $action_entity, object|null $target_entity = null, bool $forbidden = false): void
    {
        $teamIdField = Config::get('teams.foreign_keys.team_id');
        $abilityModel = TeamsFacade::instance('ability')->firstOrCreate([
            $teamIdField => $team->id,
            'entity_id' => $action_entity->id,
            'entity_type' => $action_entity::class,
            'permission_id' => $team->getPermissionIds([$permission])[0]
        ]);

        // Ensure the ability model is successfully retrieved or created
        if (! $abilityModel) {
            throw new ModelNotFoundException("Ability with permission '$permission' not found.");
        }

        // Target for ability defaults to user
        $targetEntity = $target_entity ?? $this;

        // Get relation name for ability
        $relation = $this->getRelationName($targetEntity);

        if (! method_exists($abilityModel, $relation)) {
            throw new ModelNotFoundException("Relation '$relation' not found on ability model.");
        }

        $abilityModel->{$relation}()->{$method}([$targetEntity->id => [
            'forbidden' => $forbidden,
        ]]);
    }

    /**
     * Get relation name for ability
     *
     * @param object|string $classname
     * @return string
     */
    private function getRelationName(object|string $classname): string
    {
        return  Str::plural(strtolower(class_basename(is_object($classname) ? $classname::class : $classname)));
    }

    /**
     * Check for wildcard permissions.
     *
     * @param array $userPermissions
     * @param string $permission
     * @return bool
     */
    private function checkPermissionWildcard(array $userPermissions, string $permission): bool
    {
        // Generate all possible wildcards from the permission segments
        $segments = collect(explode('.', $permission));

        $codes = $segments->map(function ($item, $key) use ($segments) {
            return $segments->take($key + 1)->implode('.') . ($key + 1 === $segments->count() ? '' : '.*') ;
        });

        // Add in the optional wildcard permissions
        if(Config::get('teams.wildcards.enabled')) {
            // Build the code collection
            $wildcardCodes = collect(Config::get('teams.wildcards.nodes'));

            // Replace codes with the new codes
            $codes = $wildcardCodes->merge($codes);
        }

        return !empty(array_intersect($codes->all(), $userPermissions));
    }
}
