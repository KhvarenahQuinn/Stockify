<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The documented sign-in accounts.
 *
 * These addresses are quoted in tests, runbooks and bug reports, so the seeder
 * that creates them is pinned here. Without this, renaming one is a silent
 * change that only shows up as a failing manual login.
 */
class TestUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RoleSeeder is what guarantees the role ids the accounts reference.
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(TestUserSeeder::class);
    }

    /**
     * @dataProvider documentedAccounts
     */
    public function test_the_documented_account_exists_with_the_exact_credentials(
        string $email,
        string $roleName,
        string $status
    ): void {
        $user = User::with('role')->where('email', $email)->first();

        $this->assertNotNull($user, "Seeded account {$email} is missing.");
        $this->assertSame($roleName, $user->role?->name, "{$email} has the wrong role.");
        $this->assertSame($status, $user->status, "{$email} has the wrong status.");
        $this->assertTrue(
            Hash::check('password', $user->password),
            "{$email} does not authenticate with the documented password."
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function documentedAccounts(): array
    {
        return [
            'admin' => ['admin@stockify.test', 'admin', 'active'],
            'manager' => ['manager@stockify.test', 'warehouse_manager', 'active'],
            'staff' => ['staff@stockify.test', 'warehouse_staff', 'active'],
            'inactive' => ['inactive@stockify.test', 'warehouse_staff', 'inactive'],
        ];
    }

    public function test_the_seeder_creates_no_duplicate_accounts(): void
    {
        $before = User::count();

        $this->seed(TestUserSeeder::class);
        $this->seed(TestUserSeeder::class);

        $this->assertSame($before, User::count(), 'Re-seeding must not add rows.');
        $this->assertSame(
            User::count(),
            User::distinct()->count('email'),
            'Every seeded address must be unique.'
        );
    }

    public function test_the_inactive_account_cannot_sign_in(): void
    {
        $this->post(route('login.store'), [
            'email' => 'inactive@stockify.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @dataProvider activeAccounts
     */
    public function test_each_active_account_can_sign_in_and_reach_the_dashboard(
        string $email,
        string $roleName
    ): void {
        $user = User::where('email', $email)->firstOrFail();

        $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        // Confirms the role the seeder attached is the one the app enforces.
        $this->assertSame($roleName, $user->fresh()->role->name);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function activeAccounts(): array
    {
        return [
            'admin' => ['admin@stockify.test', 'admin'],
            'manager' => ['manager@stockify.test', 'warehouse_manager'],
            'staff' => ['staff@stockify.test', 'warehouse_staff'],
        ];
    }

    public function test_it_refuses_to_seed_when_a_role_is_missing(): void
    {
        // Guard against a mis-ordered DatabaseSeeder producing an account with
        // a role_id the foreign key will reject.
        Role::query()->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Run RoleSeeder first');

        $this->seed(TestUserSeeder::class);
    }

    public function test_it_stays_silent_when_no_console_command_is_attached(): void
    {
        // $this->seed() runs the seeder outside a console command, so the
        // summary table must not be emitted into test output.
        $seeder = new TestUserSeeder();

        ob_start();
        $seeder->run();
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertDatabaseHas('users', ['email' => 'admin@stockify.test']);
    }
}