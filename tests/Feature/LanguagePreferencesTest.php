<?php

namespace Tests\Feature;

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
