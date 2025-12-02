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
        Schema::create('abilities', static function (Blueprint $table) {
            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->uuid('id')->primary(),
                'int' => $table->id(),
                default => $table->id(), // bigint
            };

            $teamIdField = Config::get('teams.foreign_keys.team_id', 'team_id');
            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->foreignUuid($teamIdField)->constrained()->cascadeOnDelete(),
                default => $table->foreignId($teamIdField)->constrained()->cascadeOnDelete(),
            };

            match (Config::get('teams.primary_key.type', 'bigint')) {
                'uuid' => $table->foreignUuid('permission_id')->constrained()->cascadeOnDelete(),
                default => $table->foreignId('permission_id')->constrained()->cascadeOnDelete(),
            };

            $table->string('title')->nullable();
            $table->morphs('entity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abilities');
    }
};
