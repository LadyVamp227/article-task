<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin account for the survey admin panel.
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Survey Admin',
                'password' => Hash::make('password'),
            ],
        );
    }
}
