<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * 
     * This migration removes the team_id column from the permissions table
     * for existing installations upgrading to the new version.
     * 
     * IMPORTANT: This is a breaking change migration for existing installations.
     * Only run this if you are upgrading from a previous version of the package.
     */
    public function up(): void
    {
        if (Schema::hasColumn('permissions', 'team_id')) {
            Schema::table('permissions', static function (Blueprint $table) {
                // Drop the unique index first
                $table->dropUnique(['team_id', 'code']);

                // Drop the foreign key constraint
                $table->dropForeign(['team_id']);

                // Drop the column
                $table->dropColumn('team_id');

                // Add new unique constraint on code only
                $table->unique('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it would require
        // re-associating permissions with teams, which is not possible
        // without data loss.
        throw new \RuntimeException(
            'This migration cannot be reversed. ' .
            'Permissions are now global entities and cannot be re-associated with teams.'
        );
    }
};
