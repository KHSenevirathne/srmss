<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * `php artisan db:seed` (and `migrate --seed`) run this, which delegates to
     * the SRMSS demo seeder: roles, permissions, one login per role, and the
     * example fleet/route/schedule data.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
