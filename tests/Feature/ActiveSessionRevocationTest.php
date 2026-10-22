<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Deactivating an account must actually lock it out, not just hide it from the
 * admin listing. Login already refuses inactive accounts; these cover the other
 * half, where a session issued while the account was still active stays usable.
 */
class ActiveSessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $status = 'active'): User
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        return User::factory()->create(['role_id' => $role->id, 'status' => $status]);
    }

    public function test_an_active_session_keeps_working(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * The core case: signed in, then deactivated by an admin, then tries to
     * keep using the session they already hold.
     */
    public function test_a_deactivated_user_is_logged_out_mid_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['status' => 'inactive']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    /**
     * A request that resolves the user fresh from the database, which is what
     * a real next request does.
     */
    public function test_revocation_uses_the_stored_status_not_the_cached_one(): void
    {
        $user = $this->makeUser();
        $id = $user->id;

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        User::whereKey($id)->update(['status' => 'inactive']);

        $this->actingAs(User::findOrFail($id))
            ->get(route('categories.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * The old session must not survive the logout, otherwise deactivation would
     * only work until the cookie happened to be replayed.
     */
    public function test_the_session_is_invalidated_rather_than_merely_cleared(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);
        $before = session()->getId();

        $user->update(['status' => 'inactive']);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertNotSame($before, session()->getId(), 'Session id must be regenerated on revocation.');
    }

    public function test_revocation_covers_every_protected_area(): void
    {
        foreach ([
            'dashboard',
            'categories.index',
            'suppliers.index',
            'attributes.index',
            'products.index',
            'admin.users.index',
            'settings.index',
        ] as $routeName) {
            $user = $this->makeUser();
            $user->update(['status' => 'inactive']);

            $this->actingAs($user)
                ->get(route($routeName))
                ->assertRedirect(route('login'), "{$routeName} was reachable while deactivated.");
        }
    }

    public function test_guests_are_untouched_by_the_middleware(): void
    {
        // The auth group runs Authenticate first; a guest must still land on
        // the login screen rather than hitting a null user.
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_json_request_gets_a_forbidden_rather_than_a_redirect(): void
    {
        $user = $this->makeUser();
        $user->update(['status' => 'inactive']);

        $this->actingAs($user)
            ->getJson(route('dashboard'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been deactivated. Contact an administrator.');

        $this->assertGuest();
    }

    public function test_reactivating_restores_access_on_the_next_login(): void
    {
        $user = $this->makeUser();
        $user->update(['status' => 'inactive']);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

        $user->update(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}