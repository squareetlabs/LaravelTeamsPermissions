<?php

namespace Squareetlabs\LaravelTeamsPermissions;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Squareetlabs\LaravelTeamsPermissions\Exceptions\AuditTableMissingException;
use Squareetlabs\LaravelTeamsPermissions\Support\Services\TeamsService;
use Squareetlabs\LaravelTeamsPermissions\Middleware\Ability as AbilityMiddleware;
use Squareetlabs\LaravelTeamsPermissions\Middleware\Permission as PermissionMiddleware;
use Squareetlabs\LaravelTeamsPermissions\Middleware\Role as RoleMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TeamsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/teams.php', 'teams');
    }

    /**
     * Bootstrap any application services.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'teams');

        $this->configureCommands();
        $this->configurePublishing();
        $this->registerFacades();
        $this->registerMiddlewares();
        $this->registerBladeDirectives();
        $this->validateAuditConfiguration();

        if (Config::get('teams.invitations.enabled') && Config::get('teams.invitations.routes.register')) {
            $this->registerRoutes();
        }

        if (Config::get('teams.api.enabled')) {
            $this->registerApiRoutes();
        }
    }

    /**
     * Configure publishing for the package.
     */
    protected function configurePublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $migrations = [
            __DIR__ . '/../database/migrations/create_teams_table.php' => database_path('migrations/2019_12_14_000001_create_teams_table.php'),
            __DIR__ . '/../database/migrations/create_permissions_table.php' => database_path('migrations/2019_12_14_000002_create_permissions_table.php'),
            __DIR__ . '/../database/migrations/create_roles_table.php' => database_path('migrations/2019_12_14_000003_create_roles_table.php'),
            __DIR__ . '/../database/migrations/create_team_user_table.php' => database_path('migrations/2019_12_14_000005_create_team_user_table.php'),
            __DIR__ . '/../database/migrations/create_abilities_table.php' => database_path('migrations/2019_12_14_000006_create_abilities_table.php'),
            __DIR__ . '/../database/migrations/create_entity_ability_table.php' => database_path('migrations/2019_12_14_000006_create_entity_ability_table.php'),
            __DIR__ . '/../database/migrations/create_groups_table.php' => database_path('migrations/2019_12_14_000008_create_groups_table.php'),
            __DIR__ . '/../database/migrations/create_group_user_table.php' => database_path('migrations/2019_12_14_000009_create_group_user_table.php'),
            __DIR__ . '/../database/migrations/create_entity_permission_table.php' => database_path('migrations/2019_12_14_000010_create_entity_permission_table.php'),
        ];

        if (Config::get('teams.invitations.enabled')) {
            $migrations[__DIR__ . '/../database/migrations/create_invitations_table.php'] = database_path('migrations/2019_12_14_000012_create_invitations_table.php');
        }

        if (Config::get('teams.audit.enabled')) {
            $migrations[__DIR__ . '/../database/migrations/create_team_audit_logs_table.php'] = database_path('migrations/2019_12_14_000013_create_team_audit_logs_table.php');
        }

        $migrations[__DIR__ . '/../database/migrations/add_performance_indexes.php'] = database_path('migrations/2019_12_14_000014_add_performance_indexes.php');

        $this->publishes([
            __DIR__ . '/../config/teams.php' => config_path('teams.php')
        ], 'teams-config');

        $this->publishes($migrations, 'teams-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/teams')
        ], 'teams-views');
    }

    /**
     * Configure the commands offered by the application.
     */
    protected function configureCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
            Console\MakePolicyCommand::class,
            Console\TeamsListCommand::class,
            Console\TeamsShowCommand::class,
            Console\TeamsPermissionsCommand::class,
            Console\TeamsAddMemberCommand::class,
            Console\TeamsSyncPermissionsCommand::class,
            Console\TeamsClearCacheCommand::class,
            Console\TeamsExportPermissionsCommand::class,
            Console\TeamsImportPermissionsCommand::class,
        ]);
    }

    /**
     * Register the models offered by the application.
     *
     * @throws Exception
     */
    protected function registerFacades(): void
    {
        $this->app->singleton('teams', static function () {
            return new TeamsService();
        });
    }

    /**
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => Config::get('teams.routes.prefix'),
            'middleware' => Config::get('teams.routes.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register the middlewares automatically.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function registerMiddlewares(): void
    {
        if (!$this->app['config']->get('teams.middleware.register')) {
            return;
        }

        $middlewares = [
            'ability' => AbilityMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ];

        foreach ($middlewares as $key => $class) {
            $this->app['router']->aliasMiddleware($key, $class);
        }
    }

    /**
     * Register Blade directives for team permissions.
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        Blade::if('teamRole', function ($team, $role) {
            return auth()->user()?->hasTeamRole($team, $role) ?? false;
        });

        Blade::if('teamPermission', function ($team, $permission) {
            return auth()->user()?->hasTeamPermission($team, $permission) ?? false;
        });

        Blade::if('teamAbility', function ($team, $ability, $model) {
            return auth()->user()?->hasTeamAbility($team, $ability, $model) ?? false;
        });
    }

    /**
     * Register API routes for team management.
     *
     * @return void
     */
    protected function registerApiRoutes(): void
    {
        Route::middleware(Config::get('teams.api.middleware'))
            ->prefix(Config::get('teams.api.prefix'))
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
    }

    /**
     * Validate audit configuration.
     *
     * @return void
     * @throws AuditTableMissingException
     */
    protected function validateAuditConfiguration(): void
    {
        if (!Config::get('teams.audit.enabled')) {
            return;
        }

        // No validar durante migraciones o instalación
        // La validación real se hace en AuditService cuando se intenta usar
        if ($this->app->runningInConsole()) {
            $command = $this->app->runningUnitTests() ? null : ($_SERVER['argv'][1] ?? null);
            
            // Saltar validación durante migraciones
            if (in_array($command, ['migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'migrate:status'])) {
                return;
            }
        }

        // Validar que la tabla existe si la auditoría está habilitada
        try {
            if (!Schema::hasTable('team_audit_logs')) {
                throw new AuditTableMissingException();
            }
        } catch (\Exception $e) {
            // Si hay un error de conexión a la BD, no validar aún
            // (puede ser que la BD aún no esté configurada)
            if (str_contains($e->getMessage(), 'Connection') || str_contains($e->getMessage(), 'SQLSTATE')) {
                return;
            }
            throw $e;
        }
    }
}
