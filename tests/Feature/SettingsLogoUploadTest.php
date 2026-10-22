<?php

namespace Tests\Feature;

use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Settings are administrator-only, and the logo is the one settings field that
 * goes through a file upload. storeLogo() had a `$self::` typo that fatalled on
 * every successful upload, which no existing test reached because the Form
 * Request rejected bad files before the service was ever called.
 */
class SettingsLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): void
    {
        $role = \App\Models\Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        $this->actingAs(\App\Models\User::factory()->create(['role_id' => $role->id]));
    }

    public function test_a_logo_upload_is_stored_and_recorded(): void
    {
        Storage::fake('public');
        $this->signInAsAdmin();

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
            'app_logo' => UploadedFile::fake()->image('logo.png', 40, 40),
        ])->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $path = app(SettingService::class)->get(SettingService::KEY_APP_LOGO);

        $this->assertNotNull($path, 'The logo path should have been recorded.');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Direct service coverage: this is the call the controller makes, and the
     * one that fatalled before.
     */
    public function test_store_logo_returns_a_stored_path(): void
    {
        Storage::fake('public');

        $path = app(SettingService::class)->storeLogo(
            UploadedFile::fake()->image('logo.png', 40, 40)
        );

        $this->assertStringStartsWith(SettingService::LOGO_DIRECTORY.'/', $path);
        Storage::disk(SettingService::LOGO_DISK)->assertExists($path);
    }

    public function test_store_logo_rejects_an_oversized_file(): void
    {
        Storage::fake('public');

        // A real image, then inflated past the documented ceiling, so the size
        // rule is what fails rather than the image rule.
        $oversized = UploadedFile::fake()
            ->image('logo.png', 40, 40)
            ->size(SettingService::LOGO_MAX_KILOBYTES + 1);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(SettingService::class)->storeLogo($oversized);
    }

    public function test_store_logo_accepts_a_file_at_the_limit(): void
    {
        Storage::fake('public');

        $atLimit = UploadedFile::fake()
            ->image('logo.png', 40, 40)
            ->size(SettingService::LOGO_MAX_KILOBYTES - 1);

        $this->assertIsString(app(SettingService::class)->storeLogo($atLimit));
    }

    public function test_the_logo_form_posts_as_multipart(): void
    {
        $this->signInAsAdmin();

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', escape: false)
            ->assertSee('name="app_logo"', escape: false);
    }

    public function test_a_rejected_logo_does_not_replace_the_existing_one(): void
    {
        Storage::fake('public');
        $this->signInAsAdmin();

        $service = app(SettingService::class);
        $existing = $service->storeLogo(UploadedFile::fake()->image('logo.png', 40, 40));
        // storeLogo() returns a path; recording it is the controller's job.
        $service->set(SettingService::KEY_APP_LOGO, $existing);

        // The Form Request rejects the oversized file before the controller
        // deletes anything, so the stored logo has to survive untouched.
        $this->from(route('settings.index'))->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
            'app_logo' => UploadedFile::fake()->image('big.png', 40, 40)
                ->size(SettingService::LOGO_MAX_KILOBYTES + 1),
        ])->assertSessionHasErrors('app_logo');

        Storage::disk('public')->assertExists($existing);
        $this->assertSame($existing, $service->get(SettingService::KEY_APP_LOGO));
    }

    public function test_non_admins_cannot_reach_settings(): void
    {
        $role = \App\Models\Role::create(['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff']);
        $this->actingAs(\App\Models\User::factory()->create(['role_id' => $role->id]));

        $this->get(route('settings.index'))->assertForbidden();
        $this->put(route('settings.update'), [])->assertForbidden();
    }

    public function test_an_svg_logo_is_rejected(): void
    {
        Storage::fake('public');
        $this->signInAsAdmin();

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->from(route('settings.index'))->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
            'app_logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ])->assertSessionHasErrors('app_logo');

        $this->assertNull(app(SettingService::class)->get(SettingService::KEY_APP_LOGO));
    }
}