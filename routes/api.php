<?php

use Illuminate\Support\Facades\Route;
use Squareetlabs\LaravelTeamsPermissions\Http\Controllers\TeamController;
use Squareetlabs\LaravelTeamsPermissions\Http\Controllers\TeamMemberController;
use Squareetlabs\LaravelTeamsPermissions\Http\Controllers\RoleController;

/*
|--------------------------------------------------------------------------
| Teams API Routes
|--------------------------------------------------------------------------
|
| These routes are optional and can be published to your application.
| They provide a REST API for managing teams, members, and roles.
|
*/

Route::middleware(['auth:sanctum'])->prefix('api/teams')->group(function () {
    // Team routes
    Route::get('/', [TeamController::class, 'index']);
    Route::post('/', [TeamController::class, 'store']);
    Route::get('/{team}', [TeamController::class, 'show']);
    Route::put('/{team}', [TeamController::class, 'update']);
    Route::delete('/{team}', [TeamController::class, 'destroy']);

    // Team members routes
    Route::get('/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('/{team}/members', [TeamMemberController::class, 'store']);
    Route::put('/{team}/members/{user}', [TeamMemberController::class, 'update']);
    Route::delete('/{team}/members/{user}', [TeamMemberController::class, 'destroy']);
    
    // Team roles routes
    Route::get('/{team}/roles', [RoleController::class, 'index']);
    Route::post('/{team}/roles', [RoleController::class, 'store']);
    Route::put('/{team}/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/{team}/roles/{role}', [RoleController::class, 'destroy']);

    // Team groups routes
    Route::get('/{team}/groups', [TeamGroupController::class, 'index']);
    Route::post('/{team}/groups', [TeamGroupController::class, 'store']);
    Route::put('/{team}/groups/{group}', [TeamGroupController::class, 'update']);
    Route::delete('/{team}/groups/{group}', [TeamGroupController::class, 'destroy']);

    // Team permissions routes
    Route::get('/{team}/permissions', [TeamController::class, 'permissions']);
});

