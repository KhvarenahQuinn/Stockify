<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        $users = [
            [
                'role' => 'admin',
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
            ],
            [
                'role' => 'warehouse_manager',
                'name' => 'Warehouse Manager',
                'email' => 'manager@example.com',
            ],
            [
                'role' => 'warehouse_staff',
                'name' => 'Warehouse Staff',
                'email' => 'staff@example.com',
            ],
        ];

        foreach ($users as $user) {
            $role = Role::where('name', $user['role'])->firstOrFail();

            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'role_id' => $role->id,
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );
        }
    }
}
