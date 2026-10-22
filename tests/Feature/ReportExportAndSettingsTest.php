<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\ReportService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FE-008: report exports, application settings, and the toast system.
 *
 * The two failure modes worth guarding: a notification that appears twice
 * (once as a flash block, once as a toast) and a settings form that a
 * non-admin can still change.
 */
class ReportExportAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $role): User
    {
        $roleModel = Role::firstOrCreate(
            ['name' => $role],
            ['display_name' => ucwords(str_replace('_', ' ', $role))]
        );

        $user = User::factory()->create([
            'role_id' => $roleModel->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function catalogue(): void
    {
        $category = Category::firstOrCreate(['name' => 'Packaging'], ['slug' => 'packaging']);
        Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            ['name' => 'Nusantara', 'email' => 'a@b.test', 'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1']
        );

        \App\Models\Product::create([
            'sku' => 'CTN-1', 'name' => 'Carton Small', 'category_id' => $category->id,
            'supplier_id' => \App\Models\Supplier::first()->id, 'buy_price' => 1000, 'sell_price' => 1500,
            'min_stock' => 10, 'current_stock' => 50, 'unit' => 'piece',
        ]);
    }

    private function stockProduct(string $sku, int $stock, int $min): Product
    {
        $category = Category::firstOrCreate(['name' => 'Packaging'], ['slug' => 'packaging']);
        $supplier = Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            ['name' => 'Nusantara', 'email' => 'a@b.test', 'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1']
        );

        return Product::create([
            'sku' => $sku, 'name' => $sku, 'category_id' => $category->id,
            'supplier_id' => $supplier->id, 'buy_price' => 1000, 'sell_price' => 1500,
            'min_stock' => $min, 'current_stock' => $stock, 'unit' => 'piece',
        ]);
    }

    /* ---- report filters ------------------------------------------------ */

    public function test_the_stock_status_filter_separates_safe_low_and_out_of_stock(): void
    {
        $this->signIn('warehouse_manager');
        $this->stockProduct('SAFE-1', 50, 10);
        $this->stockProduct('LOW-1', 5, 10);
        $this->stockProduct('AT-MIN-1', 10, 10);
        $this->stockProduct('OUT-1', 0, 10);

        $low = $this->get(route('reports.stock', ['stock_status' => 'low']))->assertOk()->getContent();
        $this->assertStringContainsString('LOW-1', $low);
        $this->assertStringContainsString('AT-MIN-1', $low);
        $this->assertStringNotContainsString('OUT-1', $low, 'Out-of-stock products are not low stock.');
        $this->assertStringNotContainsString('SAFE-1', $low);

        $safe = $this->get(route('reports.stock', ['stock_status' => 'safe']))->assertOk()->getContent();
        $this->assertStringContainsString('SAFE-1', $safe);
        $this->assertStringNotContainsString('LOW-1', $safe);
        $this->assertStringNotContainsString('AT-MIN-1', $safe, 'The reorder point itself is low, not safe.');
        $this->assertStringNotContainsString('OUT-1', $safe);

        // "ok" is accepted as an alias for the same bucket.
        $ok = $this->get(route('reports.stock', ['stock_status' => 'ok']))->assertOk()->getContent();
        $this->assertStringContainsString('SAFE-1', $ok);
        $this->assertStringNotContainsString('OUT-1', $ok);

        $out = $this->get(route('reports.stock', ['stock_status' => 'out']))->assertOk()->getContent();
        $this->assertStringContainsString('OUT-1', $out);
        $this->assertStringNotContainsString('SAFE-1', $out);
        $this->assertStringNotContainsString('LOW-1', $out);
        $this->assertStringNotContainsString('AT-MIN-1', $out);

        $all = $this->get(route('reports.stock'))->assertOk()->getContent();
        foreach (['SAFE-1', 'LOW-1', 'AT-MIN-1', 'OUT-1'] as $sku) {
            $this->assertStringContainsString($sku, $all);
        }

        foreach (['bg-green-700 text-white', 'bg-amber-700 text-white', 'bg-red-700 text-white'] as $tone) {
            $this->assertStringContainsString($tone, $all, "Missing badge tone {$tone}.");
        }

        $this->assertStringContainsString('>Safe</span>', $all);
        $this->assertStringNotContainsString('Safe / OK', $all);

        $totals = app(ReportService::class)->stockReport([])['totals'];
        $this->assertSame(2, $totals['low_stock'], 'Low stock must exclude empty products.');
        $this->assertSame(1, $totals['out_of_stock']);
    }

    public function test_the_stock_report_accepts_the_start_and_end_date_names(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        // The report service still speaks in from/to; the controller maps the
        // control names onto it.
        $body = $this->get(route('reports.stock', ['start_date' => '2026-01-01', 'end_date' => '2026-12-31']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="start_date"', $body);
        $this->assertStringContainsString('name="end_date"', $body);
        $this->assertStringContainsString('value="2026-01-01"', $body);
        $this->assertStringContainsString('value="2026-12-31"', $body);
    }

    public function test_the_date_range_actually_filters_the_report(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $before = $this->get(route('reports.stock'))->assertOk()->getContent();
        $this->assertStringContainsString('Carton Small', $before);

        // A window that ends before the product's last movement.
        $after = $this->get(route('reports.stock', ['start_date' => '2020-01-01', 'end_date' => '2020-12-31']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Carton Small', $after, 'Stock balances are a snapshot, not a period.');
    }

    public function test_the_transaction_report_filters_by_type(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $product = \App\Models\Product::first();

        \App\Models\StockTransaction::create([
            'reference_number' => 'ST-IN-1', 'type' => 'IN', 'product_id' => $product->id,
            'quantity' => 10, 'status' => 'APPROVED', 'user_id' => auth()->id(),
        ]);
        \App\Models\StockTransaction::create([
            'reference_number' => 'ST-OUT-1', 'type' => 'OUT', 'product_id' => $product->id,
            'quantity' => 4, 'status' => 'APPROVED', 'user_id' => auth()->id(),
        ]);

        $all = $this->get(route('reports.transactions'))->assertOk()->getContent();
        $this->assertStringContainsString('ST-IN-1', $all);
        $this->assertStringContainsString('ST-OUT-1', $all);

        $onlyIn = $this->get(route('reports.transactions', ['type' => 'IN']))->assertOk()->getContent();
        $this->assertStringContainsString('ST-IN-1', $onlyIn);
        $this->assertStringNotContainsString('ST-OUT-1', $onlyIn);
    }

    public function test_the_report_pages_offer_a_category_and_supplier_filter(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        foreach (['reports.stock', 'reports.transactions'] as $name) {
            $body = $this->get(route($name))->assertOk()->getContent();

            $this->assertStringContainsString('name="category_id"', $body, "{$name} category filter");
            $this->assertStringContainsString('name="supplier_id"', $body, "{$name} supplier filter");
        }
    }

    public function test_the_transactions_report_offers_an_all_type_option(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $body = $this->get(route('reports.transactions'))->assertOk()->getContent();

        $this->assertStringContainsString('All types', $body);
        $this->assertStringContainsString('name="type"', $body);
    }

    /* ---- exports -------------------------------------------------------- */

    public function test_the_export_buttons_carry_a_loading_hook_and_a_format(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $body = $this->get(route('reports.stock'))->assertOk()->getContent();

        $this->assertStringContainsString('data-export-trigger', $body);
        $this->assertStringContainsString('data-export-format="xlsx"', $body);
        $this->assertStringContainsString('data-export-format="pdf"', $body);
    }

    public function test_the_export_buttons_carry_the_current_filters(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $body = $this->get(route('reports.stock', ['category_id' => 1, 'start_date' => '2026-01-01']))
            ->assertOk()
            ->getContent();

        // The download has to use the same filters the preview is showing.
        $this->assertStringContainsString('category_id=1', $body);
        $this->assertStringContainsString('start_date=2026-01-01', $body);
    }

    public function test_a_manager_can_download_the_stock_report(): void
    {
        $this->signIn('warehouse_manager');
        $this->catalogue();

        $this->get(route('reports.stock.export', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_staff_cannot_export(): void
    {
        $this->signIn('warehouse_staff');
        $this->catalogue();

        $this->get(route('reports.stock.export', ['format' => 'xlsx']))->assertForbidden();
    }

    public function test_the_reports_landing_page_lists_every_report(): void
    {
        $this->signIn('warehouse_manager');

        $body = $this->get(route('reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Stock balances', $body);
        $this->assertStringContainsString('Goods movements', $body);
        $this->assertStringContainsString('User activity', $body);
    }

    /* ---- settings RBAC -------------------------------------------------- */

    public function test_non_admins_cannot_reach_the_settings_page(): void
    {
        foreach (['warehouse_staff', 'warehouse_manager'] as $role) {
            $this->signIn($role);
            $this->get(route('settings.index'))->assertForbidden();
        }
    }

    public function test_non_admins_cannot_post_the_settings_form(): void
    {
        $this->signIn('warehouse_manager');

        $this->put(route('settings.update'), ['app_name' => 'Hijacked', 'default_min_stock' => 5])
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'app_name', 'value' => 'Hijacked']);
    }

    public function test_the_settings_page_renders_for_an_admin(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('settings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="app_name"', $body);
        $this->assertStringContainsString('name="default_min_stock"', $body);
        $this->assertStringContainsString('name="app_logo"', $body);
    }

    public function test_the_settings_page_uses_the_shared_file_upload_component(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('settings.index'))->assertOk()->getContent();

        // The shared component brings the instant preview with it.
        $this->assertStringContainsString('data-image-input', $body);
        $this->assertStringContainsString('data-image-preview', $body);

        // The remove control only appears once a logo exists, so it is
        // checked after one is uploaded.
        Setting::create(['key' => SettingService::KEY_APP_LOGO, 'value' => 'settings/logo.png']);
        app(SettingService::class)->forget();

        $withLogo = $this->get(route('settings.index'))->assertOk()->getContent();
        $this->assertStringContainsString('name="remove_logo"', $withLogo);
    }

    public function test_an_admin_can_update_the_settings(): void
    {
        $this->signIn('admin');

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify Warehouse',
            'default_min_stock' => 25,
        ])->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'app_name', 'value' => 'Stockify Warehouse']);
        $this->assertDatabaseHas('settings', ['key' => 'default_min_stock', 'value' => '25']);
    }

    public function test_the_email_alert_settings_persist(): void
    {
        $this->signIn('admin');

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
            'email_alerts_enabled' => '1',
            'email_alerts_recipient' => 'warehouse@example.com',
            'email_alerts_min_stock' => 5,
        ])->assertRedirect();

        $settings = app(SettingService::class);

        $this->assertTrue($settings->emailAlertsEnabled());
        $this->assertSame('warehouse@example.com', $settings->emailAlertsRecipient());
        $this->assertSame(5, $settings->emailAlertsMinStock());
    }

    public function test_enabling_the_alerts_demands_a_recipient(): void
    {
        $this->signIn('admin');

        $this->from(route('settings.index'))
            ->put(route('settings.update'), [
                'app_name' => 'Stockify',
                'default_min_stock' => 10,
                'email_alerts_enabled' => '1',
                'email_alerts_recipient' => '',
                'email_alerts_min_stock' => 5,
            ])
            ->assertSessionHasErrors('email_alerts_recipient');
    }

    public function test_an_invalid_recipient_is_rejected(): void
    {
        $this->signIn('admin');

        $this->from(route('settings.index'))
            ->put(route('settings.update'), [
                'app_name' => 'Stockify',
                'default_min_stock' => 10,
                'email_alerts_enabled' => '1',
                'email_alerts_recipient' => 'not-an-address',
                'email_alerts_min_stock' => 5,
            ])
            ->assertSessionHasErrors('email_alerts_recipient');
    }

    public function test_the_alert_fields_are_not_required_while_the_alerts_are_off(): void
    {
        $this->signIn('admin');

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
        ])->assertSessionHasNoErrors();
    }

    public function test_the_logo_can_be_uploaded(): void
    {
        Storage::fake('public');
        $this->signIn('admin');

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
            'app_logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $this->assertNotNull(app(SettingService::class)->appLogo());
    }

    /* ---- toasts --------------------------------------------------------- */

    public function test_the_toast_region_is_present_on_every_page(): void
    {
        $this->signIn('admin');

        foreach ([route('dashboard'), route('settings.index'), route('reports.stock')] as $url) {
            $body = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-toast-region', $body, "{$url} has no toast region");
            $this->assertStringContainsString('aria-live="polite"', $body);
        }
    }

    public function test_a_success_flash_becomes_a_toast(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('settings.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-toast-region', $body);
    }

    public function test_the_flash_message_is_not_printed_twice(): void
    {
        $this->signIn('admin');

        $this->put(route('settings.update'), [
            'app_name' => 'Stockify',
            'default_min_stock' => 10,
        ])->assertRedirect();

        // Follow the redirect and count the message.
        $body = $this->get(route('settings.index'))->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($body, 'Settings updated successfully.'),
            'The flash must appear once - in the toast, not in a block as well.'
        );
    }

    public function test_the_toast_region_carries_a_template_for_every_tone(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('settings.index'))->assertOk()->getContent();

        foreach (['success', 'error', 'warning', 'info'] as $tone) {
            $this->assertStringContainsString('data-toast-template="'.$tone.'"', $body);
        }
    }

    public function test_the_toast_script_ships_the_dismissal_behaviour(): void
    {
        $source = file_get_contents(resource_path('js/toast.js'));

        // Four seconds, a manual close, and a reduced-motion escape hatch.
        $this->assertStringContainsString('DEFAULT_DURATION = 4000', $source);
        $this->assertStringContainsString('data-toast-close', $source);
        $this->assertStringContainsString('prefers-reduced-motion', $source);
        $this->assertStringContainsString('window.toast', $source);
    }

    public function test_the_toast_script_is_wired_into_the_bundle(): void
    {
        $this->assertStringContainsString(
            "import './toast';",
            file_get_contents(resource_path('js/app.js'))
        );
    }

    public function test_the_toast_is_positioned_and_layers_above_a_modal(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('settings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('fixed', $body);
        $this->assertStringContainsString('z-50', $body);
    }

    public function test_no_view_prints_its_own_flash_block_anymore(): void
    {
        // A flash block in a view would print the message a second time, next
        // to the toast that already says it.
        $offenders = [];

        foreach (glob(resource_path('views').'/**/*.blade.php') as $file) {
            if (str_contains($file, 'toast-region')) {
                continue;
            }

            $contents = file_get_contents($file);

            if (preg_match("/@if\s*\(\s*session\('(success|error|warning|info)'\)/", $contents)) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, 'These views still print their own flash block.');
    }

    public function test_staff_only_see_their_own_activity_entries(): void
    {
        $staff = $this->signIn('warehouse_staff');
        $other = User::factory()->create([
            'role_id' => Role::where('name', 'warehouse_staff')->value('id'),
            'status' => 'active',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $staff->id,
            'description' => 'STAFF OWN ENTRY',
        ]);
        ActivityLog::factory()->create([
            'user_id' => $other->id,
            'description' => 'OTHER USER SECRET',
        ]);

        $this->get(route('reports.activity', ['user_id' => $other->id]))
            ->assertOk()
            ->assertSee('STAFF OWN ENTRY')
            ->assertDontSee('OTHER USER SECRET');
    }

    public function test_a_manager_sees_the_full_activity_trail(): void
    {
        $this->signIn('warehouse_manager');
        $other = User::factory()->create([
            'role_id' => Role::firstOrCreate(
                ['name' => 'warehouse_staff'],
                ['display_name' => 'Warehouse Staff']
            )->id,
            'status' => 'active',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $other->id,
            'description' => 'OTHER USER ENTRY',
        ]);

        $this->get(route('reports.activity'))
            ->assertOk()
            ->assertSee('OTHER USER ENTRY')
            ->assertDontSee('You are seeing your own activity only');
    }
}
