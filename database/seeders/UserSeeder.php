<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The three core accounts are fixed and documented so a fresh environment is
 * always loggable. Everything after them is demo data for populating lists,
 * filters and the activity feed.
 *
 * All accounts share the password "password"; this is local development data
 * only and must never be reused for a deployed environment.
 */
class UserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $roles = Role::pluck('id', 'name');

        $core = [
            ['role' => 'admin', 'name' => 'System Administrator', 'email' => 'admin@example.com'],
            ['role' => 'warehouse_manager', 'name' => 'Warehouse Manager', 'email' => 'manager@example.com'],
            ['role' => 'warehouse_staff', 'name' => 'Warehouse Staff', 'email' => 'staff@example.com'],
        ];

        foreach ($core as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'role_id' => $roles[$user['role']],
                    'name' => $user['name'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'status' => 'active',
                ]
            );
        }

        // Weighted towards staff, which matches a real warehouse headcount, and
        // deliberately mixed on status so the active/inactive filters and the
        // EnsureUserIsActive path both have something to show.
        $demo = [
            ['Warehouse Manager', 'warehouse_manager', 'Ratna Kusuma', 'ratna.kusuma@stockify.test'],
            ['Warehouse Manager', 'warehouse_manager', 'Dimas Prasetyo', 'dimas.prasetyo@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Siti Rahayu', 'siti.rahayu@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Bagus Wicaksono', 'bagus.wicaksono@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Andi Firmansyah', 'andi.firmansyah@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Maya Lestari', 'maya.lestari@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Rudi Hartono', 'rudi.hartono@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Nur Aini', 'nur.aini@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Yoga Pratama', 'yoga.pratama@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Tika Maulida', 'tika.maulida@stockify.test'],
            ['Admin', 'admin', 'Operations Supervisor', 'eko.supervisor@stockify.test'],
            ['Warehouse Staff', 'warehouse_staff', 'Bayu Anggara', 'bayu.anggara@stockify.test'],
        ];

        // Two inactive accounts so the status filter and the revocation
        // middleware have real rows behind them.
        $inactive = ['eko.supervisor@stockify.test', 'bayu.anggara@stockify.test'];

        foreach ($demo as [$roleName, $role, $name, $email]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roles[$role],
                    'name' => $name,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'status' => in_array($email, $inactive, true) ? 'inactive' : 'active',
                ]
            );
        }
    }
}