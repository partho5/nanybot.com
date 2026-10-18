<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sample data ships a known admin password: never seed it in production.
        // Create the real admin with `php artisan blog:create-admin`.
        if (app()->isProduction()) {
            $this->command?->warn('Skipping sample data in production. Use `php artisan blog:create-admin`.');

            return;
        }

        $this->call(BlogSeeder::class);
    }
}
