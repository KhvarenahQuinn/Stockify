<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Markup regressions in the shared shell and the reusable form components.
 *
 * These are deliberately narrow — they pin the specific classes that broke
 * layout — because nothing else in the suite renders the markup far enough to
 * notice a padding or positioning class going missing.
 */
class AppShellLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    /**
     * Every web response, signed in or not, must carry the hardening headers.
     */
    public function test_web_responses_carry_the_security_headers(): void
    {
        $this->assertSecurityHeaders($this->get(route('login'))->assertOk());
    }

    public function test_authenticated_web_responses_carry_the_security_headers(): void
    {
        $this->signInAsAdmin();

        $this->assertSecurityHeaders($this->get(route('dashboard'))->assertOk());
    }

    /**
     * The shell no longer depends on fonts.googleapis.com, so the strict CSP
     * cannot silently fall back to a different typeface.
     */
    public function test_the_shell_does_not_load_third_party_fonts(): void
    {
        $body = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $body);
        $this->assertStringNotContainsString('fonts.gstatic.com', $body);
    }

    private function assertSecurityHeaders(TestResponse $response): void
    {
        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; frame-ancestors 'none'; object-src 'none'"
            );
    }

    /**
     * Modal body content sat flush against the modal edges because the shared
     * shell rendered the body slot with no padding wrapper at all.
     */
    public function test_the_modal_body_has_an_inset(): void
    {
        $this->signInAsAdmin();
        Category::create(['name' => 'Packaging', 'slug' => 'packaging']);

        $body = $this->get(route('categories.index'))->getContent();

        $this->assertStringContainsString(
            'max-h-[70vh] overflow-y-auto p-4 sm:p-6',
            $body,
            'Modal body must supply its own padding so fields are not pressed against the border.'
        );
    }

    /**
     * The "/categories/" prefix is far wider than the pl-8 default, so the slug
     * value rendered underneath it.
     */
    public function test_the_slug_field_reserves_room_for_its_prefix(): void
    {
        $this->signInAsAdmin();
        Category::create(['name' => 'Packaging', 'slug' => 'packaging']);

        $body = $this->get(route('categories.index'))->getContent();

        // The prefix renders as span content, not an attribute.
        $this->assertStringContainsString(
            '>/categories/</span>',
            $body,
            'The slug prefix should render ahead of the input value.'
        );
        $this->assertStringContainsString(
            'pl-28',
            $body,
            'Slug inputs must clear the /categories/ prefix; pl-8 is far too narrow.'
        );
    }

    /**
     * Short prefixes (Rp, SUP, PRD) keep the tight default so they do not gain
     * a wide empty gutter.
     */
    public function test_short_prefixes_keep_the_default_padding(): void
    {
        // Products are manager-owned per WORKFLOW.md, so the create modal with
        // its Rp-prefixed price fields only renders for that role.
        $role = Role::create(['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $body = $this->get(route('products.index'))->getContent();

        $this->assertStringContainsString('>Rp</span>', $body, 'Price fields keep the short Rp prefix.');
        $this->assertStringContainsString(
            'pl-8',
            $body,
            'Short prefixes should use the default inset rather than the wide slug value.'
        );
    }

    /**
     * The navbar is a fixed 64px bar spanning the full width. With the drawer at
     * top-0 the first section heading rendered underneath it.
     */
    public function test_the_sidebar_starts_below_the_fixed_navbar(): void
    {
        $this->signInAsAdmin();
        $this->makeCategoriesAndAttributes();

        $body = $this->get(route('categories.index'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<aside[^>]*\btop-16\b/',
            $body,
            'Sidebar must start at top-16 to clear the 4rem navbar.'
        );
        $this->assertMatchesRegularExpression(
            '/<aside[^>]*h-\[calc\(100vh-4rem\)\]/',
            $body,
            'Sidebar must lose the 4rem the navbar occupies, or it overflows the viewport.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<aside[^>]*\btop-0\b/',
            $body,
            'Sidebar at top-0 sits behind the navbar.'
        );
    }

    /**
     * The shell offsets the sidebar with lg:ml-64, so the two must agree on the
     * sidebar width or the content column drifts.
     */
    public function test_the_sidebar_width_matches_the_main_content_offset(): void
    {
        $this->signInAsAdmin();

        $body = $this->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('w-64', $body, 'Sidebar width drives the content offset.');
        $this->assertStringContainsString('lg:ml-64', $body, 'Main content must clear the 16rem sidebar.');
    }

    /**
     * The desktop collapse toggle shares the drawer id with the mobile
     * controls, and the icon-only links need their own accessible labels.
     */
    public function test_the_shell_renders_the_desktop_sidebar_toggle(): void
    {
        $this->signInAsAdmin();

        $body = $this->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('id="sidebar-toggle"', $body);
        $this->assertStringContainsString('aria-controls="drawer-navigation"', $body);
        $this->assertStringContainsString('data-collapse-label="Collapse sidebar"', $body);
        $this->assertStringContainsString('data-expand-label="Expand sidebar"', $body);
        $this->assertStringContainsString('data-sidebar-tooltip="Dashboard"', $body);
    }

    private function makeCategoriesAndAttributes(): void
    {
        if (Category::count() === 0) {
            Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        }

        if (\App\Models\Attribute::count() === 0) {
            $attribute = \App\Models\Attribute::create(['name' => 'Size']);
            \App\Models\AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'L']);
        }
    }
}
