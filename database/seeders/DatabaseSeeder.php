<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create a default admin user
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'usertype' => 'QA Admin',
                'email' => 'admin@hau.edu.ph',
                'password' => bcrypt('password'),
            ]
        );

        // 1b. Create secondary admin user
        User::updateOrCreate(
            ['username' => 'qaoadmin'],
            [
                'name' => 'QAO Admin',
                'first_name' => 'QAO',
                'last_name' => 'Admin',
                'usertype' => 'QA Admin',
                'email' => 'qaoadmin@hau.edu.ph',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Call the real database seeder
        $this->call(HauRealDataSeeder::class);
        $this->call(ResponsibleUnitSeeder::class);
    }
}