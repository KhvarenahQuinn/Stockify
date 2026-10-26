<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Language is a per-user preference. Every signed-in role must be able to
 * change it without being given access to application-wide settings.
 */
class LanguagePreferencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function roles(): array
    {
        return ['admin', 'warehouse_manager', 'warehouse_staff'];
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucwords(str_replace('_', ' ', $roleName))],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'locale' => 'en',
        ]);
    }

    public function test_every_role_can_open_the_language_preferences_page(): void
    {
        foreach ($this->roles() as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(route('preferences.edit'))
                ->assertOk()
                ->assertSee('Language preferences')
                ->assertSee('name="locale"', escape: false)
                ->assertSee('value="id"', escape: false);
        }
    }

    public function test_every_role_can_change_its_own_language(): void
    {
        foreach ($this->roles() as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->put(route('preferences.update'), ['locale' => 'id'])
                ->assertRedirect(route('preferences.edit'))
                ->assertSessionHas('success', 'Bahasa berhasil diperbarui.');

            $this->assertSame('id', $user->fresh()->locale);
        }
    }

    public function test_the_saved_language_applies_to_the_next_request(): void
    {
        $user = $this->makeUser('warehouse_staff');

        $this->actingAs($user)
            ->put(route('preferences.update'), ['locale' => 'id'])
            ->assertRedirect(route('preferences.edit'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dasbor');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('preferences.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_an_unsupported_language_is_rejected(): void
    {
        $user = $this->makeUser('warehouse_manager');

        $this->actingAs($user)
            ->from(route('preferences.edit'))
            ->put(route('preferences.update'), ['locale' => 'fr'])
            ->assertRedirect(route('preferences.edit'))
            ->assertSessionHasErrors('locale');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_the_user_menu_exposes_the_language_link_for_every_role(): void
    {
        foreach ($this->roles() as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee(route('preferences.edit'), escape: false);
        }
    }

    public function test_the_login_screen_offers_both_languages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('locale.update'), escape: false)
            ->assertSee('name="locale"', escape: false)
            ->assertSee('value="en"', escape: false)
            ->assertSee('value="id"', escape: false)
            ->assertSee('English')
            ->assertSee('Bahasa Indonesia');
    }

    public function test_a_guest_can_switch_the_login_language(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'id'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'id')
            ->assertSessionHas(SetLocaleMiddleware::GUEST_LOCALE_KEY, 'id');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('Kata sandi');
    }

    public function test_an_unsupported_login_language_is_rejected(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('locale');
    }

    public function test_a_login_language_choice_is_saved_to_the_account(): void
    {
        $user = $this->makeUser('warehouse_staff');

        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'id']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame('id', $user->fresh()->locale);
    }

    public function test_an_untouched_login_form_does_not_overwrite_the_saved_language(): void
    {
        $user = $this->makeUser('warehouse_staff');
        $user->forceFill(['locale' => 'id'])->save();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame('id', $user->fresh()->locale);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dasbor');
    }

    public function test_application_settings_stay_admin_only(): void
    {
        foreach (['warehouse_manager', 'warehouse_staff'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(route('settings.index'))
                ->assertForbidden();
        }
    }
}
