<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The four documented accounts used for local sign-in and for the security
 * tests that need a known-inactive user.
 *
 * These are deliberately separate from UserSeeder's demo population: the
 * emails here are quoted in tests, in runbooks and in bug reports, so they must
 * not drift when the demo data is shuffled. DatabaseSeeder runs this after
 * UserSeeder so these definitions are authoritative if the two ever overlap.
 *
 * Local development credentials only. Never reuse them in a deployed
 * environment.
 */
class TestUserSeeder extends Seeder
{
    /**
     * Every account shares this password so a sign-in can be reproduced from
     * the printed summary without consulting the source.
     */
    public const TEST_PASSWORD = 'password';

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function accounts(): array
    {
        return [
            // role name, email, status
            ['admin', 'admin@stockify.test', 'active'],
            ['warehouse_manager', 'manager@stockify.test', 'active'],
            ['warehouse_staff', 'staff@stockify.test', 'active'],
            // Sits behind EnsureUserIsActive and the login-time status check.
            ['warehouse_staff', 'inactive@stockify.test', 'inactive'],
        ];
    }

    public function run(): void
    {
        $roles = Role::pluck('id', 'name');
        $names = [
            'admin' => 'System Administrator',
            'warehouse_manager' => 'Warehouse Manager',
            'warehouse_staff' => 'Warehouse Staff',
        ];

        $rows = [];

        foreach (self::accounts() as [$roleName, $email, $status]) {
            if (! isset($roles[$roleName])) {
                // Failing loudly beats seeding a user whose role_id points at
                // nothing and gets rejected by the foreign key.
                throw new \RuntimeException(
                    "TestUserSeeder needs the \"{$roleName}\" role. Run RoleSeeder first."
                );
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roles[$roleName],
                    'name' => $names[$roleName],
                    // Re-set on every run so re-seeding also restores a known
                    // password rather than leaving an unknown hash behind.
                    'password' => Hash::make(self::TEST_PASSWORD),
                    'status' => $status,
                ]
            );

            $rows[] = [$roleName, $email, self::TEST_PASSWORD, $status];
        }

        $this->printSummary($rows);
    }

    /**
     * Echo the credentials so they do not have to be read out of the source.
     *
     * $command is only set when the seeder runs through a console command, so
     * this stays silent when a seeder is invoked programmatically (in a test,
     * or from another seeder's run()).
     *
     * @param  array<int, array<int, string>>  $rows
     */
    protected function printSummary(array $rows): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->newLine();
        $this->command->info('Test accounts ready — local development only:');

        $this->command->table(
            ['Role', 'Email', 'Password', 'Status'],
            $rows
        );

        $this->command->line(
            '  <fg=yellow>inactive@stockify.test</> is deliberately deactivated and must not be able to sign in.'
        );
        $this->command->newLine();
    }
}