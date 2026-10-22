<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * layouts.app is shared by the authenticated screens and by the public
 * practice pages, so the navbar has to render both ways. These assertions pin
 * the guest branch down; without them an unguarded auth()->user() call only
 * fails on a public route and slips past the authenticated test coverage.
 *
 * /practice/* is the route that actually exercises this: it has no auth
 * middleware and renders layouts.app, whereas / now redirects to /login.
 */
class NavbarGuestRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shared_shell_renders_for_a_guest(): void
    {
        $response = $this->get('/practice/1');

        $response->assertOk()
            // Chrome that must survive regardless of auth state.
            ->assertSee('Stockify')
            ->assertSee('id="theme-toggle"', escape: false)
            // The guest branch of the shell.
            ->assertSee('Log in')
            ->assertSee(route('login'), escape: false);
    }

    public function test_a_guest_is_never_shown_the_user_menu(): void
    {
        $this->get('/practice/1')
            ->assertOk()
            ->assertDontSee('id="user-menu-button"', escape: false)
            ->assertDontSee('id="dropdown-user"', escape: false)
            ->assertDontSee('Sign out')
            ->assertDontSee('action="'.route('logout').'"', escape: false);
    }

    public function test_the_guest_shell_carries_no_php_warnings(): void
    {
        $body = $this->get('/practice/1')->getContent();

        foreach (['Attempt to read property', 'Undefined variable', 'Warning:', 'Notice:', 'on null'] as $marker) {
            $this->assertStringNotContainsString($marker, $body, "Guest render leaked \"{$marker}\".");
        }
    }

    /**
     * The login screen uses its own layout. Pulling the Flowbite starter chrome
     * into it is what put a "Flowbite" navbar and demo links on the page.
     */
    public function test_the_login_screen_carries_no_flowbite_demo_chrome(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('stockify-logo', escape: false)
            ->assertDontSee('Flowbite')
            ->assertDontSee('Team')
            ->assertDontSee('Pricing')
            // No signed-in shell on an auth screen.
            ->assertDontSee('id="user-menu-button"', escape: false)
            ->assertDontSee('Sign out');
    }

    public function test_a_signed_in_user_still_gets_the_full_profile_menu(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Admin')
            // "BS" from the first and last name.
            ->assertSee('BS')
            ->assertSee('id="user-menu-button"', escape: false)
            ->assertSee('id="dropdown-user"', escape: false)
            ->assertSee('Sign out')
            ->assertSee(route('logout'), escape: false);
    }

    /**
     * A signed-in user has no use for the sign-in link, and the root should
     * hand them straight to the dashboard.
     */
    public function test_a_signed_in_user_is_not_offered_the_login_link(): void
    {
        $role = Role::create(['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Log in<', escape: false)
            ->assertDontSee(route('login'), escape: false);
    }

    public function test_the_root_redirects_a_signed_in_user_to_the_dashboard(): void
    {
        $role = Role::create(['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    /**
     * Every authenticated screen renders through layouts.app, so a navbar that
     * only works on the dashboard is still a regression.
     */
    public function test_the_navbar_renders_on_every_authenticated_screen(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id, 'name' => 'Budi Santoso']);

        foreach ([
            'dashboard',
            'categories.index',
            'suppliers.index',
            'attributes.index',
            'products.index',
            'admin.users.index',
        ] as $routeName) {
            $content = $this->actingAs($user)->get(route($routeName))->assertOk()->getContent();

            $this->assertStringContainsString(
                'id="user-menu-button"',
                $content,
                "Navbar user menu missing on {$routeName}"
            );
            $this->assertStringContainsString($user->name, $content, "Navbar name missing on {$routeName}");
        }
    }
}