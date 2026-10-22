<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed order is dictated by foreign keys, not preference:
 *
 *   roles -> users -> categories -> suppliers -> attributes -> products
 *         -> transactions -> activity logs -> settings
 *
 * Users precede everything because transactions and activity rows carry a
 * user_id. Attributes precede products because the seeder resolves
 * attribute_value ids when syncing each product's specifications.
 *
 * TestUserSeeder runs last of the user seeders: its addresses are quoted in
 * tests and runbooks, so they must not be reshaped by the demo data.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            TestUserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            StockTransactionSeeder::class,
            ActivityLogSeeder::class,
            SettingSeeder::class,
        ]);
    }
}