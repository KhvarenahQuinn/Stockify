<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shell is shared by every authenticated page, so these assertions on
 * /dashboard cover the sidebar, the navbar and the responsive drawer markup.
 */
class RoleAwareNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function signInAs(string $roleName): User
    {
        $role = Role::create([
            'name' => $roleName,
            'display_name' => ucwords(str_replace('_', ' ', $roleName)),
        ]);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_sees_every_menu_group(): void
    {
        $this->signInAs('admin');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administration')
            ->assertSee('Warehouse')
            ->assertSee('Reports')
            ->assertSee('User Management')
            ->assertSee('Settings')
            ->assertSee(route('settings.index'), escape: false);
    }

    public function test_manager_sees_stock_and_reports_but_not_administration(): void
    {
        $this->signInAs('warehouse_manager');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stock Transactions')
            ->assertSee('Stock Report')
            ->assertSee('Transaction Report')
            ->assertDontSee('User Management')
            ->assertDontSee(route('admin.users.index'), escape: false)
            ->assertDontSee(route('settings.index'), escape: false);
    }

    public function test_staff_only_sees_the_task_queue(): void
    {
        $this->signInAs('warehouse_staff');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Task Queue')
            ->assertSee('Dashboard')
            ->assertSee('Input Stock Opname')
            ->assertDontSee('Stock Report')
            ->assertDontSee(route('admin.users.index'), escape: false)
            ->assertDontSee(route('settings.index'), escape: false);
    }

    public function test_navbar_exposes_the_user_role_badge_and_logout(): void
    {
        $user = $this->signInAs('warehouse_manager');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Warehouse Manager')
            ->assertSee(route('logout'), escape: false);
    }

    public function test_shell_renders_the_responsive_drawer_controls(): void
    {
        $this->signInAs('warehouse_staff');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-drawer-target="drawer-navigation"', escape: false)
            ->assertSee('data-drawer-toggle="drawer-navigation"', escape: false)
            ->assertSee('aria-controls="drawer-navigation"', escape: false)
            ->assertSee('id="drawer-navigation"', escape: false)
            ->assertSee('lg:hidden', escape: false);
    }
}
