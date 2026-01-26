<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample regular users
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ],
            [
                'name' => 'John Manager',
                'email' => 'john@factory.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'Sarah Supervisor',
                'email' => 'sarah@factory.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'Mike Operator',
                'email' => 'mike@factory.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'Emma Coordinator',
                'email' => 'emma@factory.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
