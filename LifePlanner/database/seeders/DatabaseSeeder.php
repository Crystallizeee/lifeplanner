<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default user
        $user = User::firstOrCreate(
            ['email' => 'admin@lifeplanner.local'],
            [
                'name' => 'LifePlanner User',
                'password' => bcrypt('password123'),
                'currency' => 'IDR',
            ]
        );

        // Seed premium categories for this user
        $user->seedDefaultCategories();
    }
}
