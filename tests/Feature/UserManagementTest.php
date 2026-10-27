<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/**
 * FE-002: the admin user management screen. Covers the listing contract, the
 * search and role filter, the status toggle, and create/update round trips.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        Role::create(['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager']);
        Role::create(['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff']);

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);
    }

    private function makeUser(string $name, string $email, string $roleName, string $status = 'active'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role_id' => Role::where('name', $roleName)->value('id'),
            'status' => $status,
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_it_lists_users_with_their_role_and_status(): void
    {
        $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');
        $this->makeUser('Bagus Wicaksono', 'bagus@stockify.test', 'warehouse_manager', 'inactive');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Siti Rahayu')
            ->assertSee('siti@stockify.test')
            ->assertSee('Warehouse Staff')
            ->assertSee('Warehouse Manager')
            ->assertSee('Active')
            ->assertSee('Inactive')
            ->assertSee(route('admin.users.toggle-status', $this->admin), escape: false)
            ->assertSee(route('admin.users.destroy', $this->admin), escape: false);
    }

    public function test_search_filters_by_name_and_email(): void
    {
        $target = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');
        $this->makeUser('Bagus Wicaksono', 'bagus@stockify.test', 'warehouse_manager');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['q' => 'Siti']))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee('bagus@stockify.test')
            ->assertSee('1 account');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['q' => 'bagus@stockify.test']))
            ->assertOk()
            ->assertDontSee($target->email);
    }

    public function test_role_filter_narrows_the_listing(): void
    {
        $staff = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');
        $manager = $this->makeUser('Bagus Wicaksono', 'bagus@stockify.test', 'warehouse_manager');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'warehouse_staff']))
            ->assertOk()
            ->assertSee($staff->email)
            ->assertDontSee($manager->email);
    }

    public function test_an_unknown_role_filter_is_ignored(): void
    {
        $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'superuser']))
            ->assertOk()
            ->assertSee('siti@stockify.test');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertSee('siti@stockify.test');
    }

    public function test_toggle_status_flips_the_account_and_redirects_to_the_listing(): void
    {
        $user = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        $this->actingAs($this->admin)
            ->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_it_creates_a_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@stockify.test',
                'phone' => '081200000000',
                'role_id' => Role::where('name', 'warehouse_manager')->value('id'),
                'password' => 'warehouse123',
                'password_confirmation' => 'warehouse123',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'dewi@stockify.test']);
    }

    public function test_create_rejects_a_duplicate_email_and_redisplays_the_errors(): void
    {
        $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.store'), [
                'name' => 'Siti Rahayu',
                'email' => 'siti@stockify.test',
                'role_id' => Role::where('name', 'warehouse_staff')->value('id'),
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(1, User::where('email', 'siti@stockify.test')->count());
    }

    public function test_it_updates_a_user(): void
    {
        $user = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Siti Rahmawati',
                'email' => 'siti.rahmawati@stockify.test',
                'role_id' => Role::where('name', 'warehouse_manager')->value('id'),
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame('Siti Rahmawati', $user->fresh()->name);
        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_the_reset_password_modal_stays_hidden_until_its_route_exists(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('data-modal-target="reset-password-', escape: false);
    }

    public function test_non_admins_cannot_reach_user_management(): void
    {
        $staff = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        // Staff are redirected to their dashboard with a warning rather than
        // shown a 403 page, so the assertion follows StaffAuthRedirect.
        $this->actingAs($staff)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning');
    }

    public function test_changing_a_password_rotates_the_remember_token(): void
    {
        $user = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');
        $user->forceFill(['remember_token' => 'old-remember-token'])->save();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'status' => 'active',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', __('You cannot delete your own account.'));

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_the_last_active_administrator_cannot_be_deleted_from_the_service(): void
    {
        $staff = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');
        $this->actingAs($staff);

        try {
            app(UserService::class)->deleteUser($this->admin->id);
            $this->fail('The last active administrator must not be deletable.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame(
                __('The last active administrator cannot be deleted.'),
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_the_last_active_administrator_cannot_be_deactivated(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.toggle-status', $this->admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', __('The last active administrator cannot be deactivated.'));

        $this->assertSame('active', $this->admin->fresh()->status);
    }

    public function test_the_last_active_administrator_cannot_be_demoted(): void
    {
        $staffRoleId = Role::where('name', 'warehouse_staff')->value('id');

        $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role_id' => $staffRoleId,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', __('The last active administrator cannot be deactivated or demoted.'));

        $this->assertSame($this->adminRole->id, $this->admin->fresh()->role_id);
    }

    public function test_an_admin_can_still_delete_another_account(): void
    {
        $user = $this->makeUser('Siti Rahayu', 'siti@stockify.test', 'warehouse_staff');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
