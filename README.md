# Squareetlabs/LaravelTeamsPermissions

[![Latest Stable Version](https://poser.pugx.org/squareetlabs/laravel-teams-permissions/v/stable)](https://packagist.org/packages/squareetlabs/laravel-teams-permissions)
[![Total Downloads](https://poser.pugx.org/squareetlabs/laravel-teams-permissions/downloads)](https://packagist.org/packages/squareetlabs/laravel-teams-permissions)
[![PHP Version Require](https://poser.pugx.org/squareetlabs/laravel-teams-permissions/require/php)](https://packagist.org/packages/squareetlabs/laravel-teams-permissions)
[![License](https://poser.pugx.org/squareetlabs/laravel-teams-permissions/license)](https://packagist.org/packages/squareetlabs/laravel-teams-permissions)

A comprehensive Laravel package designed for advanced team-based permission management in multi-tenant applications. This package provides a flexible and powerful system for organizing users into teams, assigning granular permissions through roles and groups, and managing entity-specific access controls.

**Core Functionality:**

- **Team Management**: Create and manage teams with owners and members. Each team operates as an independent workspace with its own set of roles, permissions, and members.

- **Role-Based Access Control (RBAC)**: Define custom roles for each team with specific permission sets. Roles can be assigned to users within a team, providing a flexible way to manage access levels. Team owners automatically have full access to all permissions.

- **Permission System**: Implement fine-grained permissions using a code-based system (e.g., `posts.create`, `users.edit`). Permissions are global entities that can be assigned to roles and groups across multiple teams. Supports wildcard permissions for flexible access patterns.

- **Group Management**: Organize users into groups within teams or globally. Groups can have their own permission sets, and permissions assigned to a group take precedence over individual user permissions within a team. This allows for efficient permission management when multiple users need the same access level.

- **Global Groups**: Create groups without team association to grant users access across all teams with the group's permissions. Perfect for scenarios like support teams, administrators, or auditors who need consistent access across multiple teams without being individually added to each one.

- **Entity-Specific Abilities**: Grant or deny permissions for specific model instances (e.g., allowing a user to edit a particular post but not others). This provides the most granular level of access control, enabling fine-tuned permissions for individual resources.

- **Multi-Tenant Support**: Built from the ground up for multi-tenant applications where each team represents a tenant. Teams are completely isolated, ensuring data security and access control between different tenants.

- **Caching & Performance**: Optional intelligent caching system to optimize permission checks, reducing database queries and improving application performance.

- **Audit Logging**: Optional comprehensive audit trail that logs all team-related actions including role assignments, permission changes, and member additions/removals.

- **REST API**: Optional complete REST API for team management, enabling frontend applications and third-party integrations to manage teams programmatically.

- **Laravel Integration**: Seamlessly integrates with Laravel's built-in authorization system, including Policies, Blade directives, and middleware for route protection.

## Key Features

- ✅ **Team Management**: Create and manage teams with owners and members
- ✅ **Roles & Permissions**: Flexible role system with granular permissions
- ✅ **Groups**: Organize users into groups with shared permissions
- ✅ **Abilities**: Entity-specific permissions for individual models
- ✅ **Smart Caching**: Caching system to optimize permission checks
- ✅ **Audit Logging**: Complete action logging for teams (optional)
- ✅ **REST API**: Complete API for team management (optional)
- ✅ **Blade Directives**: Blade directives for permission checks in views
- ✅ **Policies**: Integration with Laravel's Policy system
- ✅ **Rate Limiting**: Protection against invitation spam
- ✅ **Middleware**: Middleware for route protection
- ✅ **Artisan Commands**: CLI tools for management

## Requirements

- PHP >= 8.1
- Laravel 8.x, 9.x, 10.x, 11.x or 12.x

## Installation

### 1. Install the Package

```bash
composer require squareetlabs/laravel-teams-permissions
```

### 2. Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="Squareetlabs\LaravelTeamsPermissions\TeamsServiceProvider"
```

This will publish:
- `config/teams.php` - Configuration file
- Database migrations

### 3. Configure the User Model

Add the `HasTeams` trait to your `User` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Squareetlabs\LaravelTeamsPermissions\Traits\HasTeams;

class User extends Model
{
    use HasTeams;
    
    // ... rest of your code
}
```

### 4. Run Migrations

> ⚠️ **IMPORTANT**: Always do backups before running migrations.

```bash
php artisan migrate
```

> [!NOTE]
> If you wish to use custom foreign keys and table names, modify `config/teams.php` before running migrations.

### 5. Optional Configuration

#### Enable Caching

To improve performance, enable caching in `.env`:

```env
TEAMS_CACHE_ENABLED=true
TEAMS_CACHE_DRIVER=redis
TEAMS_CACHE_TTL=3600
```

#### Enable Audit Logging

To log all team actions:

```env
TEAMS_AUDIT_ENABLED=true
TEAMS_AUDIT_LOG_CHANNEL=teams
```

> [!NOTE]
> If you enable audit logging after running migrations, you'll need to publish and run the audit migration:
> ```bash
> php artisan vendor:publish --tag=teams-migrations
> php artisan migrate
> ```

#### Enable REST API

To expose a REST API for team management:

```env
TEAMS_API_ENABLED=true
```

## Configuration

The configuration file `config/teams.php` contains all options:

### Custom Models

```php
'models' => [
    'user' => App\Models\User::class,
    'team' => Squareetlabs\LaravelTeamsPermissions\Models\Team::class,
    // ... other models
],
```

### Cache

```php
'cache' => [
    'enabled' => env('TEAMS_CACHE_ENABLED', true),
    'driver' => env('TEAMS_CACHE_DRIVER', 'redis'),
    'ttl' => env('TEAMS_CACHE_TTL', 3600),
    'prefix' => 'teams_permissions',
    'tags' => true,
],
```

### Audit

```php
'audit' => [
    'enabled' => env('TEAMS_AUDIT_ENABLED', false),
    'log_channel' => env('TEAMS_AUDIT_LOG_CHANNEL', 'teams'),
    'events' => [
        'role_assigned',
        'permission_granted',
        'permission_revoked',
        'team_member_added',
        'team_member_removed',
    ],
],
```

See `config/teams.php` for all available options.

## Basic Usage

### Creating a Team

```php
use Squareetlabs\LaravelTeamsPermissions\Models\Team;

$team = Team::create([
    'name' => 'My Team',
    'user_id' => auth()->id(),
]);
```

### Adding Roles and Permissions

```php
// Add role with permissions
$team->addRole('admin', [
    'posts.*',
    'users.*',
    'settings.edit',
], 'Administrator', 'Role with all permissions');

$team->addRole('editor', [
    'posts.view',
    'posts.create',
    'posts.edit',
], 'Editor', 'Can manage posts');
```

### Adding Team Members

```php
// Add user with a role
$team->addUser($user, 'editor');

// Update user's role
$team->updateUser($user, 'admin');

// Remove user
$team->deleteUser($user);
```

### Checking Permissions

```php
// Check if user has a permission
if ($user->hasTeamPermission($team, 'posts.create')) {
    // User can create posts
}

// Check if user has a role
if ($user->hasTeamRole($team, 'admin')) {
    // User is admin
}

// Check specific ability on an entity
if ($user->hasTeamAbility($team, 'edit', $post)) {
    // User can edit this specific post
}
```

## Teams

### Available Methods

```php
// Access the team's owner
$team->owner

// Get all team users (excluding owner)
$team->users()

// Get all users including owner
$team->allUsers()

// Check if a user belongs to the team
$team->hasUser($user)

// Add user with a role (by ID or code)
$team->addUser($user, 'admin')

// Update user's role
$team->updateUser($user, 'editor')

// Remove user from team
$team->deleteUser($user)

// Invite user by email
$team->inviteUser('user@example.com', 'member')

// Accept invitation
$team->inviteAccept($invitation_id)

// Get all team abilities
$team->abilities()

// Get all team roles
$team->roles()

// Get user's role in the team
$team->userRole($user)

// Check if team has a role
$team->hasRole('admin') // or null to check if has any role

// Get role by ID or code
$team->getRole('admin')

// Add new role
$team->addRole($code, $permissions, $name, $description)

// Update role
$team->updateRole('admin', $newPermissions, $name, $description)

// Delete role
$team->deleteRole('admin')

// Get all groups
$team->groups()

// Get group by ID or code
$team->getGroup('moderators')

// Add new group
$team->addGroup($code, $permissions, $name)

// Update group
$team->updateGroup('moderators', $newPermissions, $name)

// Delete group
$team->deleteGroup('moderators')

// Check if team has user with email
$team->hasUserWithEmail('user@example.com')

// Check if user has permission in team
$team->userHasPermission($user, 'posts.create', $require = false)

// Get all invitations
$team->invitations()
```

## Users

The `HasTeams` trait provides the following methods:

```php
// Get teams the user belongs to
$user->teams

// Get teams the user owns
$user->ownedTeams

// Get all teams (owned and belongs to)
$user->allTeams()

// Check if user owns a team
$user->ownsTeam($team)

// Check if user belongs to a team
$user->belongsToTeam($team)

// Get user's role in a team
$user->teamRole($team)

// Check if user has a role (or roles) in a team
// $require = true: all roles are required
// $require = false: at least one of the roles
$user->hasTeamRole($team, 'admin', $require = false)
$user->hasTeamRole($team, ['admin', 'editor'], $require = false)

// Get all user's permissions for a team
// $scope: 'role', 'group', or null for all
$user->teamPermissions($team, $scope = null)

// Check if user has a permission (or permissions) in a team
// $require = true: all permissions are required
// $require = false: at least one of the permissions
// $scope: 'role', 'group', or null for all
$user->hasTeamPermission($team, 'posts.create', $require = false, $scope = null)
$user->hasTeamPermission($team, ['posts.create', 'posts.edit'], $require = false)

// Get user's abilities for a specific entity
$user->teamAbilities($team, $entity, $forbidden = false)

// Check if user has an ability on an entity
$user->hasTeamAbility($team, 'edit', $post)

// Allow ability for user on an entity
$user->allowTeamAbility($team, 'edit', $post)

// Forbid ability for user on an entity
$user->forbidTeamAbility($team, 'edit', $post)

// Delete ability
$user->deleteTeamAbility($team, 'edit', $post)

// Scope for eager loading permissions
User::withTeamPermissions()->get()
```

## Roles & Permissions

### Creating Roles with Permissions

```php
$team->addRole('admin', [
    'posts.*',           // All post permissions
    'users.view',        // View users
    'users.create',      // Create users
    'users.edit',        // Edit users
    'users.delete',      // Delete users
    'settings.*',        // All settings permissions
], 'Administrator', 'Role with full access');
```

### Wildcard Permissions

You can use wildcards for permissions:

- `posts.*` - All permissions starting with `posts.`
- `*` - All permissions (if enabled in config)

### Checking Permissions

```php
// Check simple permission
if ($user->hasTeamPermission($team, 'posts.create')) {
    // User can create posts
}

// Check multiple permissions (OR)
if ($user->hasTeamPermission($team, ['posts.create', 'posts.edit'], false)) {
    // User can create OR edit posts
}

// Check multiple permissions (AND)
if ($user->hasTeamPermission($team, ['posts.create', 'posts.edit'], true)) {
    // User can create AND edit posts
}
```

### Wildcard Permissions

You can enable wildcard permissions in configuration:

```php
'wildcards' => [
    'enabled' => true,
    'nodes' => [
        '*',
        '*.*',
        'all'
    ]
]
```

Users with these permissions will have full access to the team.

## Abilities

Abilities allow specific permissions for individual entities.

### Adding an Ability

```php
// Allow user to edit a specific post
$user->allowTeamAbility($team, 'edit', $post);

// Forbid user to edit a specific post
$user->forbidTeamAbility($team, 'edit', $post);
```

### Checking an Ability

```php
if ($user->hasTeamAbility($team, 'edit', $post)) {
    // User can edit this specific post
}
```

### Access Levels

Abilities use an access level system:

| Level | Value | Description |
|-------|-------|-------------|
| `DEFAULT` | 0 | No explicit permissions |
| `FORBIDDEN` | 1 | Access denied |
| `ROLE_ALLOWED` | 2 | Allowed by role |
| `ROLE_FORBIDDEN` | 3 | Forbidden by role |
| `GROUP_ALLOWED` | 4 | Allowed by group |
| `GROUP_FORBIDDEN` | 5 | Forbidden by group |
| `USER_ALLOWED` | 5 | Specifically allowed to user |
| `USER_FORBIDDEN` | 6 | Specifically forbidden to user |
| `GLOBAL_ALLOWED` | 6 | Global permissions |

Access is granted if the `allowed` level >= `forbidden` level.

## Groups

Groups allow organizing users with shared permissions.

### Creating and Managing Groups

```php
// Add group
$team->addGroup('moderators', [
    'posts.moderate',
    'comments.moderate',
], 'Moderators');

// Update group
$team->updateGroup('moderators', [
    'posts.moderate',
    'comments.moderate',
    'users.moderate',
], 'Moderators');

// Delete group
$team->deleteGroup('moderators');

// Get group
$group = $team->getGroup('moderators');

// Add users to group
$group->users()->attach($user);
// or multiple users
$group->users()->attach([$user1->id, $user2->id]);

// Remove users from group
$group->users()->detach($user);
```

### Global Groups

Groups without `team_id` are global and apply to all teams:

```php
// Create global group (team_id = null)
$globalGroup = Group::create([
    'code' => 'support',
    'name' => 'Support Team',
    'team_id' => null,
]);
```

## Middleware

The package provides middleware for route protection.

### Configuration

Middleware is automatically registered as `role`, `permission`, and `ability`.

### Usage in Routes

```php
// Check role
Route::middleware(['role:admin,team_id'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Check permission
Route::middleware(['permission:posts.create,team_id'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});

// Check ability
Route::middleware(['ability:edit,App\Models\Post,post_id'])->group(function () {
    Route::put('/posts/{post_id}', [PostController::class, 'update']);
});
```

### OR Operations

```php
// User must have admin OR root
Route::middleware(['role:admin|root,team_id'])->group(function () {
    // ...
});
```

### AND Operations

```php
// User must have admin AND editor
Route::middleware(['role:admin|editor,team_id,require'])->group(function () {
    // ...
});
```

## Blade Directives

The package includes Blade directives for permission checks in views:

```blade
{{-- Check role --}}
@teamRole($team, 'admin')
    <button>Admin Panel</button>
@endteamRole

{{-- Check permission --}}
@teamPermission($team, 'posts.create')
    <a href="{{ route('posts.create') }}">New Post</a>
@endteamPermission

{{-- Check ability --}}
@teamAbility($team, 'edit', $post)
    <button>Edit Post</button>
@endteamAbility
```

## Policies

The package integrates with Laravel's Policy system.

### Generate a Policy

```bash
php artisan teams:policy PostPolicy --model=Post
```

This generates a policy extending `TeamPolicy`:

```php
namespace App\Policies;

use App\Models\Post;
use Squareetlabs\LaravelTeamsPermissions\Policies\TeamPolicy;

class PostPolicy extends TeamPolicy
{
    public function view(User $user, Post $post): bool
    {
        $team = $this->getTeamFromModel($post);
        return $this->checkTeamPermission($user, $team, 'posts.view');
    }

    public function update(User $user, Post $post): bool
    {
        $team = $this->getTeamFromModel($post);
        return $this->checkTeamAbility($user, $team, 'posts.update', $post);
    }
}
```

### Using the Policy

```php
// In a controller
if ($user->can('view', $post)) {
    // User can view the post
}

// In a view
@can('update', $post)
    <button>Edit</button>
@endcan
```

## REST API

If you enable the REST API, you'll have access to complete endpoints for team management.

### Enable API

```env
TEAMS_API_ENABLED=true
```

### Available Endpoints

```
GET    /api/teams                    - List teams
POST   /api/teams                    - Create team
GET    /api/teams/{team}             - View team
PUT    /api/teams/{team}             - Update team
DELETE /api/teams/{team}             - Delete team

GET    /api/teams/{team}/members     - List members
POST   /api/teams/{team}/members     - Add member
PUT    /api/teams/{team}/members/{user} - Update member role
DELETE /api/teams/{team}/members/{user} - Remove member

GET    /api/teams/{team}/roles       - List roles
POST   /api/teams/{team}/roles       - Create role
PUT    /api/teams/{team}/roles/{role} - Update role
DELETE /api/teams/{team}/roles/{role} - Delete role

GET    /api/teams/{team}/groups      - List groups
POST   /api/teams/{team}/groups      - Create group
PUT    /api/teams/{team}/groups/{group} - Update group
DELETE /api/teams/{team}/groups/{group} - Delete group

GET    /api/teams/{team}/permissions - List permissions
```

### Authentication

The API requires Sanctum authentication:

```php
// In your frontend application
axios.get('/api/teams', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
});
```

## Artisan Commands

The package includes several useful commands:

### Team Management

```bash
# List all teams
php artisan teams:list

# View team details
php artisan teams:show {team}

# View team permissions
php artisan teams:permissions {team}

# Add member to team
php artisan teams:add-member {team} {user} {role}
```

### Permission Management

```bash
# Sync permissions from configuration
php artisan teams:sync-permissions

# Export team permissions
php artisan teams:export-permissions {team} --format=json

# Import permissions to team
php artisan teams:import-permissions {team} --file=permissions.json
```

### Utilities

```bash
# Clear permissions cache
php artisan teams:clear-cache

# Generate a policy
php artisan teams:policy PostPolicy --model=Post
```

## Caching

The caching system significantly improves permission check performance.

### Configuration

```php
'cache' => [
    'enabled' => true,
    'driver' => 'redis',
    'ttl' => 3600, // 1 hour
    'prefix' => 'teams_permissions',
    'tags' => true,
],
```

### Clear Cache

```bash
php artisan teams:clear-cache
```

Or programmatically:

```php
use Squareetlabs\LaravelTeamsPermissions\Support\Services\PermissionCache;

$cache = new PermissionCache();
$cache->flush();
```

## Audit Logging

The audit system logs all important team actions.

### Enable Audit

```env
TEAMS_AUDIT_ENABLED=true
TEAMS_AUDIT_LOG_CHANNEL=teams
```

### Audited Events

- `role_assigned` - Role assignment
- `permission_granted` - Permission granted
- `permission_revoked` - Permission revoked
- `team_member_added` - Member added
- `team_member_removed` - Member removed

### Query Logs

```php
use Squareetlabs\LaravelTeamsPermissions\Models\TeamAuditLog;

// Get logs for a team
$logs = TeamAuditLog::where('team_id', $team->id)->get();

// Get logs for a user
$logs = TeamAuditLog::where('user_id', $user->id)->get();

// Get logs for a specific action
$logs = TeamAuditLog::where('action', 'team_member_added')->get();
```

> [!NOTE]
> If you enable audit logging after running migrations, you'll need to run:
> ```bash
> php artisan vendor:publish --tag=teams-migrations
> php artisan migrate
> ```

## Events

The package fires events for important actions:

```php
use Squareetlabs\LaravelTeamsPermissions\Events\TeamMemberAdded;
use Squareetlabs\LaravelTeamsPermissions\Events\TeamMemberRemoved;

Event::listen(TeamMemberAdded::class, function ($team, $user) {
    // Notify user they were added
});

Event::listen(TeamMemberRemoved::class, function ($team, $user) {
    // Notify user they were removed
});
```

### Available Events

- `TeamCreating` / `TeamCreated`
- `TeamUpdating` / `TeamUpdated`
- `TeamDeleted`
- `TeamMemberAdding` / `TeamMemberAdded`
- `TeamMemberRemoving` / `TeamMemberRemoved`
- `TeamMemberUpdated`
- `TeamMemberInviting` / `TeamMemberInvited`

## Validation

The package includes validation rules:

```php
use Squareetlabs\LaravelTeamsPermissions\Rules\ValidPermission;

$request->validate([
    'permission' => ['required', new ValidPermission()],
]);
```

## Usage Examples

### Blog System with Teams

```php
// Create team
$team = Team::create([
    'name' => 'Blog Team',
    'user_id' => auth()->id(),
]);

// Add roles
$team->addRole('editor', ['posts.*', 'comments.moderate'], 'Editor');
$team->addRole('author', ['posts.create', 'posts.edit'], 'Author');
$team->addRole('viewer', ['posts.view'], 'Viewer');

// In a controller
public function store(Request $request)
{
    $team = Team::find($request->team_id);
    
    if (!auth()->user()->hasTeamPermission($team, 'posts.create')) {
        abort(403);
    }
    
    // Create post...
}
```

### Multi-tenant SaaS Application

```php
// Each client has their own team
$clientTeam = Team::create([
    'name' => $client->name,
    'user_id' => $client->owner_id,
]);

// Client-specific roles
$clientTeam->addRole('admin', ['*'], 'Administrator');
$clientTeam->addRole('user', ['dashboard.view', 'reports.view'], 'User');

// Check access in middleware
Route::middleware(['permission:dashboard.view,team_id'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## Testing

The package includes factories and seeders for testing:

```php
use Squareetlabs\LaravelTeamsPermissions\Database\Factories\TeamFactory;

$team = TeamFactory::new()
    ->withRoles()
    ->withGroups()
    ->create();
```

## Troubleshooting

### Error: "Model class for key user not found"

Make sure your User model is configured in `config/teams.php`:

```php
'models' => [
    'user' => App\Models\User::class,
],
```

### Error: "AuditTableMissingException"

If you enable audit logging after running migrations:

```bash
php artisan vendor:publish --tag=teams-migrations
php artisan migrate
```

Or disable audit logging in `config/teams.php`:

```php
'audit' => [
    'enabled' => false,
],
```

### Cache Not Updating

Clear cache manually:

```bash
php artisan teams:clear-cache
```

## API Documentation

### Classes and Methods

#### `HasTeams` Trait

Methods available on User model:

- `ownsTeam(Team $team): bool` - Check if user owns the team
- `allTeams(): Collection` - Get all teams (owned and belongs to)
- `ownedTeams(): HasMany` - Get teams user owns
- `teams(): BelongsToMany` - Get teams user belongs to
- `belongsToTeam(Team $team): bool` - Check if user belongs to team
- `teamRole(Team $team): ?Role` - Get user's role in team
- `hasTeamRole(Team $team, string|array $roles, bool $require = false): bool` - Check if user has role(s)
- `teamPermissions(Team $team, ?string $scope = null): array` - Get user's permissions for team
- `hasTeamPermission(Team $team, string|array $permissions, bool $require = false, ?string $scope = null): bool` - Check if user has permission(s)
- `teamAbilities(Team $team, Model $entity, bool $forbidden = false): Collection` - Get user's abilities for entity
- `hasTeamAbility(Team $team, string $permission, Model $action_entity): bool` - Check if user has ability
- `allowTeamAbility(Team $team, string $permission, Model $action_entity, ?Model $target_entity = null): void` - Allow ability
- `forbidTeamAbility(Team $team, string $permission, Model $action_entity, ?Model $target_entity = null): void` - Forbid ability
- `deleteTeamAbility(Team $team, string $permission, Model $action_entity, ?Model $target_entity = null): void` - Delete ability
- `scopeWithTeamPermissions($query)` - Eager load team permissions

#### `HasMembers` Trait

Methods available on Team model:

- `owner(): BelongsTo` - Get team owner
- `users(): BelongsToMany` - Get team members
- `abilities(): HasMany` - Get team abilities
- `roles(): HasMany` - Get team roles
- `groups(): HasMany` - Get team groups
- `invitations(): HasMany` - Get pending invitations
- `allUsers(): Collection` - Get all users including owner
- `hasUser(User $user): bool` - Check if user is member
- `addUser(User $user, string $role_keyword): void` - Add user to team
- `updateUser(User $user, string $role_keyword): void` - Update user's role
- `deleteUser(User $user): void` - Remove user from team
- `inviteUser(string $email, int|string $keyword): void` - Invite user by email
- `inviteAccept(int $invitation_id): void` - Accept invitation
- `hasUserWithEmail(string $email): bool` - Check if team has user with email
- `userRole(User $user): ?Role` - Get user's role in team
- `userHasPermission(User $user, string|array $permissions, bool $require = false): bool` - Check if user has permission
- `hasRole(int|string|null $keyword = null): bool` - Check if team has role
- `getRole(int|string $keyword): ?Role` - Get role by ID or code
- `addRole(string $code, array $permissions, ?string $name = null, ?string $description = null): Role` - Add role
- `updateRole(int|string $keyword, array $permissions, ?string $name = null, ?string $description = null): Role` - Update role
- `deleteRole(int|string $keyword): bool` - Delete role
- `hasGroup(int|string|null $keyword = null): bool` - Check if team has group
- `getGroup(int|string $keyword): ?Group` - Get group by ID or code
- `addGroup(string $code, array $permissions = [], ?string $name = null): Group` - Add group
- `updateGroup(int|string $keyword, array $permissions = [], ?string $name = null): Group` - Update group
- `deleteGroup(int|string $keyword): bool` - Delete group
- `purge(): void` - Delete team and all relations
- `getPermissionIds(array $codes): array` - Get permission IDs for codes

#### `PermissionCache` Service

- `remember(string $key, callable $callback, ?int $ttl = null): mixed` - Cache a value
- `flush(): void` - Flush all cache
- `forget(string $key): void` - Forget specific key
- `get(string $key, mixed $default = null): mixed` - Get cached value

#### `AuditService` Service

- `log(string $action, mixed $team, mixed $user, mixed $subject = null, ?array $oldValues = null, ?array $newValues = null): void` - Log audit event
- `logRoleAssigned(mixed $team, mixed $user, mixed $role): void` - Log role assignment
- `logPermissionGranted(mixed $team, mixed $user, string $permission): void` - Log permission granted
- `logPermissionRevoked(mixed $team, mixed $user, string $permission): void` - Log permission revoked
- `logTeamMemberAdded(mixed $team, mixed $user, mixed $member, mixed $role): void` - Log member added
- `logTeamMemberRemoved(mixed $team, mixed $user, mixed $member): void` - Log member removed

#### `TeamPolicy` Base Class

- `checkTeamPermission(Model $user, Model $team, string $permission): bool` - Check team permission
- `checkTeamAbility(Model $user, Model $team, string $ability, Model $model): bool` - Check team ability
- `checkTeamRole(Model $user, Model $team, string|array $roles): bool` - Check team role
- `getTeamFromModel(Model $model): ?Model` - Get team from model

## Contributing

Contributions are welcome. Please:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full list of changes.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

For support, please open an issue on [GitHub](https://github.com/squareetlabs/laravel-teams-permissions/issues).

## Authors

- **Yuri Gerassimov** - [jurager01@gmail.com](mailto:jurager01@gmail.com)
- **Squareetlabs** - [contact@squareetlabs.com](mailto:contact@squareetlabs.com)
