<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Keep the authorisation model in sync via `php artisan migrate` alone, so a
     * teammate who pulls and migrates automatically gets new roles/permissions
     * without a separate seeding step.
     *
     * The seeder is the single source of truth and is idempotent (findOrCreate +
     * syncPermissions), so this safely adds the `driver` role and the trip-approval
     * permissions to databases that were seeded before they existed - without
     * touching users, their role assignments, or any other data.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // The authorisation model is owned by the seeder; nothing to roll back.
    }
};
