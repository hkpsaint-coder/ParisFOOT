<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = config('admin.password');

        if (! is_string($adminPassword) || trim($adminPassword) === '' || $adminPassword === 'password') {
            throw new \LogicException('Set a unique ADMIN_PASSWORD in .env before seeding the administrator account.');
        }

        User::updateOrCreate(['email' => mb_strtolower(trim(config('admin.email')))], [
            'name' => config('admin.name'),
            'password' => $adminPassword,
            'role' => 'super_admin',
        ]);
    }
}
