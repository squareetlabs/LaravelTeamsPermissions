<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    | Customize middleware behavior and handling of unauthorized requests.
    */
    'middleware' => [

        // Whether to automatically register team middleware in the service provider.
        'register' => true,

        // Response method upon unauthorized access: abort or redirect.
        'handling' => 'abort',

        // Handlers for unauthorized access, aligned with the handling method.
        'handlers' => [
            'abort' => [
                'code' => 403,
                'message' => 'User does not have any of the necessary access rights.',
            ],
            'redirect' => [
                'url' => '/home',
                'message' => [
                    'key' => 'error',
                    'content' => '',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    | Define the models used for team functionalities and role-based access.
    */
    'models' => [
        'user' => App\Models\User::class,
        'team' => Squareetlabs\LaravelTeamsPermissions\Models\Team::class,
        'ability' => Squareetlabs\LaravelTeamsPermissions\Models\Ability::class,
        'permission' => Squareetlabs\LaravelTeamsPermissions\Models\Permission::class,
        'group' => Squareetlabs\LaravelTeamsPermissions\Models\Group::class,
        'invitation' => Squareetlabs\LaravelTeamsPermissions\Models\Invitation::class,
        'membership' => Squareetlabs\LaravelTeamsPermissions\Models\Membership::class,
        'role' => Squareetlabs\LaravelTeamsPermissions\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    | Specify table names linked to team-related models.
    */
    'tables' => [
        'teams' => 'teams',
        'team_user' => 'team_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Foreign Keys
    |--------------------------------------------------------------------------
    | Foreign keys for table relationships in package models.
    */
    'foreign_keys' => [
        'team_id' => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Primary Key Configuration
    |--------------------------------------------------------------------------
    | Configure the type of primary keys used in package tables.
    | Supported types: 'int', 'bigint', 'uuid'
    | 
    | - 'int': Uses standard integer auto-increment IDs
    | - 'bigint': Uses big integer auto-increment IDs (default, recommended)
    | - 'uuid': Uses UUID v4 strings as primary keys
    */
    'primary_key' => [
        'type' => env('TEAMS_PRIMARY_KEY_TYPE', 'bigint'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Lifecycle
    |--------------------------------------------------------------------------
    | Configure request lifecycle options
    */
    'request' => [
        // Enabling this option caches the permission decision for the request
        'cache_decisions' => false,
    ],


    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    | Configures the team invitation feature, allowing users to be invited to join teams.
    */
    'invitations' => [

        'enabled' => true,

        'routes' => [
            'register' => true,
            'url' => '/invitation/{invitation_id}/accept',
            'middleware' => 'web'
        ],

        'rate_limit' => [
            'max_attempts' => env('TEAMS_INVITATIONS_RATE_LIMIT_MAX', 10),
            'decay_seconds' => env('TEAMS_INVITATIONS_RATE_LIMIT_DECAY', 3600),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard Permissions
    |--------------------------------------------------------------------------
    | Configure wildcard permission nodes, allowing you to specify super admin
    | permission node(s) that allows a user to perform all actions on a team.
    */
    'wildcards' => [
        'enabled' => false,
        'nodes' => [
            '*',
            '*.*',
            'all'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Configure caching for permissions and team data to improve performance.
    */
    'cache' => [
        'enabled' => env('TEAMS_CACHE_ENABLED', true),
        'driver' => env('TEAMS_CACHE_DRIVER', 'redis'),
        'ttl' => env('TEAMS_CACHE_TTL', 3600),
        'prefix' => 'teams_permissions',
        'tags' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    | Configure audit logging for team-related actions.
    |
    | IMPORTANT: Si habilitas la auditoría después de ejecutar las migraciones,
    | necesitarás publicar y ejecutar la migración de auditoría:
    | php artisan vendor:publish --tag=teams-migrations
    | php artisan migrate
    */
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

    /*
    |--------------------------------------------------------------------------
    | API Routes Configuration
    |--------------------------------------------------------------------------
    | Configure optional REST API routes for team management.
    */
    'api' => [
        'enabled' => env('TEAMS_API_ENABLED', false),
        'prefix' => 'api/teams',
        'middleware' => ['auth:sanctum'],
    ],

];
