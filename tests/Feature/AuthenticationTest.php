<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication rules. The status check is the important one: User Management
 * can deactivate an account, and that has to mean the account is locked out
 * rather than merely hidden from the admin listing.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $status = 'active', string $roleName = 'warehouse_staff'): User
    {
        $role = Role::create([
            'name' => $roleName,
            'display_name' => ucwords(str_replace('_', ' ', $roleName)),
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => $status,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_an_active_user_can_sign_in(): void
    {
        $user = $this->makeUser();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_login_form_exposes_a_password_visibility_toggle_and_autofill_hints(): void
    {
        $body = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('autocomplete="username"', $body);
        $this->assertStringContainsString('autocomplete="current-password"', $body);
        $this->assertStringContainsString('data-password-toggle', $body);
        $this->assertStringContainsString('data-password-input', $body);
        $this->assertStringContainsString('aria-label="Show password"', $body);
        $this->assertStringContainsString('aria-pressed="false"', $body);
        $this->assertStringContainsString('aria-controls="password"', $body);
        $this->assertMatchesRegularExpression(
            '/<button\b[^>]*\btype="button"[^>]*\bdata-password-toggle-button\b/',
            $body
        );
    }

    public function test_the_password_toggle_script_switches_the_input_type(): void
    {
        $source = file_get_contents(resource_path('js/auth-ui.js'));

        $this->assertStringContainsString("input.type = reveal ? 'text' : 'password'", $source);
        $this->assertStringContainsString("'Hide password' : 'Show password'", $source);
        $this->assertStringContainsString(
            "import './auth-ui';",
            file_get_contents(resource_path('js/app.js'))
        );
    }

    public function test_remember_me_sets_a_persistent_recaller_cookie(): void
    {
        $user = $this->makeUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_remember_me_is_optional(): void
    {
        $user = $this->makeUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    /**
     * Remember me must not turn "Sign out" into a no-op. Logging out has to
     * clear the recaller and rotate the token so the old cookie is dead.
     */
    public function test_signing_out_revokes_the_remember_me_cookie(): void
    {
        $user = $this->makeUser();
        $originalToken = $user->remember_token;

        $login = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $recaller = $login->getCookie(Auth::guard('web')->getRecallerName())->getValue();

        $logout = $this->withCookie(Auth::guard('web')->getRecallerName(), $recaller)
            ->post(route('logout'));

        $logout->assertRedirect();
        $logout->assertCookieExpired(Auth::guard('web')->getRecallerName());
        $this->assertGuest();
        $this->assertNotSame($originalToken, $user->fresh()->remember_token);
    }

    /**
     * The point of remember me is not the cookie on the login response; it is
     * being signed back in after the short-lived session is gone.
     */
    public function test_the_recaller_cookie_restores_the_session_without_a_session_cookie(): void
    {
        $user = $this->makeUser();

        $login = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $recaller = $login->getCookie(Auth::guard('web')->getRecallerName())->getValue();

        // Drop the session and the guard's cached user, leaving only the
        // long-lived recaller a real browser would still have.
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $this->withCookie(Auth::guard('web')->getRecallerName(), $recaller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * The bug this guards: a deactivated account still held a valid password
     * and could reach the dashboard.
     */
    public function test_an_inactive_user_is_refused(): void
    {
        $user = $this->makeUser('inactive');

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_inactive_user_gets_the_same_message_as_a_wrong_password(): void
    {
        $inactive = $this->makeUser('inactive');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $inactive->email,
            'password' => 'password',
        ]);

        $inactiveMessage = session('errors')->first('email');

        auth()->logout();
        session()->flush();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $inactive->email,
            'password' => 'wrong-password',
        ]);

        $wrongPasswordMessage = session('errors')->first('email');

        // Identical messaging, so the form cannot be used to discover which
        // addresses are registered or merely deactivated.
        $this->assertSame(__('auth.failed'), $inactiveMessage);
        $this->assertSame($inactiveMessage, $wrongPasswordMessage);
    }

    public function test_reactivating_the_account_restores_access(): void
    {
        $user = $this->makeUser('inactive');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $user->update(['status' => 'active']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_still_rejected(): void
    {
        $user = $this->makeUser();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_both_fields(): void
    {
        $this->from(route('login'))->post(route('login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_a_signed_in_user_can_sign_out(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }

    /**
     * Locking out an inactive manager matters more than it looks: WORKFLOW.md
     * gives that role full product CRUD and bulk import.
     */
    public function test_a_deactivated_manager_cannot_reach_the_screens_it_would_otherwise_own(): void
    {
        $manager = $this->makeUser('inactive', 'warehouse_manager');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        // And there is no way in by posting the write endpoint directly.
        $this->post(route('products.store'), [])->assertRedirect(route('login'));
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $email = 'throttle-target@stockify.test';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many login attempts',
            (string) session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_a_password_change_revokes_an_existing_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);
        $this->get(route('dashboard'))->assertOk();

        $user->forceFill(['password' => Hash::make('a-new-password')])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
