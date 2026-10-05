<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'admin', 'display_name' => 'Administrator'],
            ['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager'],
            ['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff'],
        ] as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
