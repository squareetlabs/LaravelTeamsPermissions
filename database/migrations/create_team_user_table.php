<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Config::get('teams.tables.team_user', 'team_user'), static function (Blueprint $table) {
            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->uuid('id')->primary(),
                'int' => $table->id(),
                default => $table->id(), // bigint
            };

            $teamIdField = Config::get('teams.foreign_keys.team_id', 'team_id');
            $teamsTable = Config::get('teams.tables.teams');

            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->foreignUuid($teamIdField)->constrained($teamsTable)->cascadeOnUpdate()->restrictOnDelete(),
                default => $table->foreignId($teamIdField)->constrained($teamsTable)->cascadeOnUpdate()->restrictOnDelete(),
            };

            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->foreignUuid('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete(),
                default => $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete(),
            };

            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->foreignUuid('role_id')->constrained('roles')->cascadeOnUpdate()->restrictOnDelete(),
                default => $table->foreignId('role_id')->constrained('roles')->cascadeOnUpdate()->restrictOnDelete(),
            };

            $table->timestamps();

            $table->unique([$teamIdField, 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
