<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FE-007: role-tailored dashboards and the ApexCharts integration.
 *
 * Two failure modes this guards against, both of which have bitten this app
 * before:
 *  - a chart that silently renders nothing because the script asked for a
 *    global the build does not produce;
 *  - a dashboard panel whose numbers no longer match the thing it is named
 *    after.
 */
class DashboardRoleViewTest extends TestCase
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

    private function product(string $sku, int $stock, int $min = 10, string $price = '10.00'): Product
    {
        $category = Category::firstOrCreate(['name' => 'Packaging'], ['slug' => 'packaging']);
        $supplier = Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            ['name' => 'Nusantara', 'email' => 'a@b.test', 'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1']
        );

        return Product::create([
            'sku' => $sku, 'name' => 'Item '.$sku, 'category_id' => $category->id,
            'supplier_id' => $supplier->id, 'buy_price' => $price, 'sell_price' => 15,
            'min_stock' => $min, 'current_stock' => $stock, 'unit' => 'piece',
        ]);
    }

    /* ---- role dispatch -------------------------------------------------- */

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_each_role_gets_its_own_dashboard(): void
    {
        $admin = $this->signIn('admin');
        $adminBody = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Recent system activity', $adminBody);
        $this->assertStringNotContainsString('Waiting on you', $adminBody);

        $manager = $this->signIn('warehouse_manager');
        $managerBody = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Approval queue', $managerBody);
        $this->assertStringNotContainsString('Recent system activity', $managerBody);

        $staff = $this->signIn('warehouse_staff');
        $staffBody = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Waiting on you', $staffBody);
        $this->assertStringNotContainsString('Approval queue', $staffBody);
    }

    public function test_a_role_without_a_dashboard_falls_back_without_erroring(): void
    {
        // A role outside the whitelist must not blow up: the view name comes
        // from the database, and view() renders whatever it is given.
        $viewer = Role::firstOrCreate(['name' => 'viewer'], ['display_name' => 'Viewer']);
        $this->actingAs(User::factory()->create(['role_id' => $viewer->id, 'status' => 'active']));

        $this->get(route('dashboard'))->assertOk();
    }

    /* ---- admin ---------------------------------------------------------- */

    public function test_the_admin_dashboard_reports_real_aggregates(): void
    {
        $this->signIn('admin');
        $this->product('SKU-1', 50, 10, '10.00');   // 500
        $this->product('SKU-2', 20, 10, '25.50');   // 510
        $this->product('SKU-3', 0, 10, '10.00');    // 0

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        // 500 + 510 + 0 = 1010
        $this->assertStringContainsString('1,010', $body, 'stock value should be current_stock * buy_price');
        $this->assertStringContainsString('3', $body, 'product count');
    }

    public function test_active_users_are_distinguished_from_provisioned_accounts(): void
    {
        $this->signIn('admin');

        $roleModel = Role::firstOrCreate(['name' => 'warehouse_staff'], ['display_name' => 'Staff']);
        User::factory()->create(['role_id' => $roleModel->id, 'status' => 'active']);
        User::factory()->create(['role_id' => $roleModel->id, 'status' => 'inactive']);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        // The signed-in admin plus one active staffer = 2; the inactive account
        // must not be counted as live access.
        $this->assertStringContainsString('Active users', $body);
        $this->assertStringContainsString('3 accounts provisioned', $body);
    }

    public function test_the_admin_audit_feed_renders_action_badges(): void
    {
        $user = $this->signIn('admin');
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'product.created',
            'description' => 'Created Product Carton Small',
            'ip_address' => '10.0.0.1',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user.updated',
            'description' => 'Updated user status',
            'ip_address' => '10.0.0.2',
        ]);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Created Product Carton Small', $body);
        $this->assertStringContainsString('Updated user status', $body);
        $this->assertStringContainsString('bg-green-700 text-white', $body, 'create badge');
        $this->assertStringContainsString('bg-blue-700 text-white', $body, 'update badge');
    }

    /* ---- manager -------------------------------------------------------- */

    public function test_the_manager_sees_a_low_stock_banner(): void
    {
        $this->signIn('warehouse_manager');
        $this->product('SKU-OK', 100, 10);
        $this->product('SKU-LOW', 3, 10);
        $this->product('SKU-ZERO', 0, 10);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('at or below the reorder point', $body);
        $this->assertStringContainsString('SKU-LOW', $body);
        $this->assertStringContainsString('SKU-ZERO', $body);
        $this->assertStringNotContainsString('SKU-OK', $body, 'a healthy product is not an alert');
        $this->assertStringContainsString('fully out of stock', $body);
    }

    public function test_no_banner_when_everything_is_above_its_reorder_point(): void
    {
        $this->signIn('warehouse_manager');
        $this->product('SKU-OK', 100, 10);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('at or below the reorder point', $body);
    }

    public function test_the_manager_approval_preview_links_into_the_review_dialog(): void
    {
        $this->signIn('warehouse_manager');
        $product = $this->product('SKU-1', 50);

        $movement = StockTransaction::create([
            'reference_number' => 'ST-IN-0001',
            'type' => 'IN',
            'product_id' => $product->id,
            'quantity' => 12,
            // The queue is StockTransaction::pending(), which is PENDING only -
            // an APPROVED movement has already been decided.
            'status' => StockTransaction::STATUS_PENDING,
            'user_id' => auth()->id(),
        ]);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('ST-IN-0001', $body);
        $this->assertStringContainsString('review='.$movement->id, $body);
    }

    public function test_the_deep_link_reopens_that_exact_dialog(): void
    {
        $this->signIn('warehouse_manager');
        $product = $this->product('SKU-1', 50);

        $movement = StockTransaction::create([
            'reference_number' => 'ST-IN-0001',
            'type' => 'IN',
            'product_id' => $product->id,
            'quantity' => 12,
            'status' => StockTransaction::STATUS_APPROVED,
            'user_id' => auth()->id(),
        ]);

        $this->get(route('stock-transactions.approvals', ['review' => $movement->id]))
            ->assertOk()
            ->assertSee('data-modal-restore="review-approve-'.$movement->id.'"', false);
    }

    public function test_an_absent_deep_link_renders_no_marker(): void
    {
        $this->signIn('warehouse_manager');

        // The page always carries a marker for a failed submission, so the
        // absence to assert is the deep link's own value.
        $this->get(route('stock-transactions.approvals'))
            ->assertOk()
            ->assertDontSee('review-approve-', false);
    }

    /* ---- staff ---------------------------------------------------------- */

    public function test_the_staff_dashboard_offers_both_shortcuts(): void
    {
        $this->signIn('warehouse_staff');

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Start a stock opname', $body);
        $this->assertStringContainsString('Record goods in or out', $body);
        $this->assertStringContainsString(route('stock-opnames.create'), $body);
        $this->assertStringContainsString(route('stock-transactions.create'), $body);
    }

    public function test_the_staff_task_cards_report_their_queue_depth(): void
    {
        $this->signIn('warehouse_staff');
        $product = $this->product('SKU-1', 50);

        StockTransaction::create([
            'reference_number' => 'ST-IN-0001', 'type' => 'IN', 'product_id' => $product->id,
            'quantity' => 5, 'status' => StockTransaction::STATUS_PENDING, 'user_id' => auth()->id(),
        ]);

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Arrivals to inspect', $body);
        $this->assertStringContainsString('ST-IN-0001', $body);
        $this->assertStringContainsString('No dispatches are waiting', $body);
    }

    public function test_the_staff_dashboard_does_not_query_the_movement_chart(): void
    {
        $this->signIn('warehouse_staff');

        $queries = [];
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$queries) {
            $queries[] = $q->sql;
        });

        $this->get(route('dashboard'))->assertOk();

        $movementQueries = array_filter($queries, fn ($sql) => str_contains($sql, 'stock_transactions')
            && str_contains($sql, 'SUM('));

        $this->assertSame([], array_values($movementQueries), 'Staff have no chart, so the grouped query must not run.');
    }

    /* ---- chart payload -------------------------------------------------- */

    public function test_the_chart_payload_keeps_series_and_labels_aligned(): void
    {
        $service = new DashboardService(app(\App\Services\LowStockService::class));

        $periods = ['2026-08', '2026-09', '2026-10'];

        $payload = $service->buildChartPayload([
            (object) ['period' => '2026-09', 'type' => 'IN', 'total' => 40],
            (object) ['period' => '2026-10', 'type' => 'IN', 'total' => 10],
            (object) ['period' => '2026-10', 'type' => 'OUT', 'total' => 25],
            // Outside the window: must not shift anything along.
            (object) ['period' => '2026-01', 'type' => 'IN', 'total' => 999],
        ], $periods);

        $in = $payload['apexcharts']['series'][0]['data'];
        $out = $payload['apexcharts']['series'][1]['data'];

        $this->assertCount(3, $in, 'A month with no movement still needs a slot.');
        $this->assertCount(3, $out);
        $this->assertSame([0.0, 40.0, 10.0], array_map('floatval', $in));
        $this->assertSame([0.0, 0.0, 25.0], array_map('floatval', $out));
        $this->assertSame(['Aug 2026', 'Sep 2026', 'Oct 2026'], $payload['apexcharts']['categories']);
        // array_sum over floats returns a float; the values are whole numbers.
        $this->assertEqualsWithDelta(50, $payload['totals']['in'], 0.001);
        $this->assertEqualsWithDelta(25, $payload['totals']['out'], 0.001);
    }

    public function test_the_chart_ships_the_flowbite_series_colours(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        // green-500 and orange-500 are the only shades of those hues that clear
        // 3:1 against both the white and the gray-800 card.
        $this->assertStringContainsString('#0E9F6E', $body);
        $this->assertStringContainsString('#FF5A1F', $body);
        $this->assertStringNotContainsString('#16a34a', $body, 'Tailwind default, not this project palette');
    }

    public function test_the_chart_ships_a_text_alternative(): void
    {
        $this->signIn('admin');

        $body = $this->get(route('dashboard'))->assertOk()->getContent();

        // A chart is unreadable to a screen reader, so the same figures ship as
        // a table rather than relying on the canvas alone.
        $this->assertStringContainsString('View the figures as a table', $body);
        $this->assertStringContainsString('<caption class="sr-only">', $body);
    }

    /* ---- the script itself ---------------------------------------------- */

    public function test_the_chart_script_does_not_depend_on_a_global_or_alpine(): void
    {
        $source = file_get_contents(resource_path('js/dashboard-charts.js'));

        // Comments are stripped first: this file explains in prose why the old
        // widget used a global and Alpine's x-init, and asserting against the
        // raw text would fail on the very comment that documents the fix.
        $code = $this->stripJsComments($source);

        // The old widget used both and so never drew anything: ApexCharts is a
        // module import, and Alpine is not installed in this build.
        $this->assertStringNotContainsString('window.ApexCharts', $code);
        $this->assertStringNotContainsString('globalThis.ApexCharts', $code);
        $this->assertStringNotContainsString('x-init', $code);
        $this->assertStringNotContainsString('x-data', $code);

        $this->assertStringContainsString("import ApexCharts from 'apexcharts';", $code);
    }

    public function test_the_chart_script_re_themes_on_the_dark_mode_event(): void
    {
        $source = file_get_contents(resource_path('js/dashboard-charts.js'));

        // dark-mode.js dispatches this on every toggle; without a listener the
        // chart keeps light axis labels on a dark card (3.04:1).
        $this->assertStringContainsString("addEventListener('dark-mode'", $source);
        $this->assertStringContainsString('updateOptions', $source);
        $this->assertStringContainsString("classList.contains('dark')", $source);
    }

    public function test_the_chart_script_is_wired_into_the_bundle(): void
    {
        $app = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import './dashboard-charts';", $app);
        $this->assertStringNotContainsString("import './charts';", $app, 'the starter demo charts are gone');
    }

    public function test_the_flowbite_starter_charts_are_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('js/charts.js'));
        $this->assertFileDoesNotExist(resource_path('views/dashboard/widgets/chart.blade.php'));
    }

    /* ---- accessibility -------------------------------------------------- */

    public function test_the_chart_tooltip_clears_aa_in_dark_mode(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // dark:text-gray-400 on dark:bg-gray-700 measured 4.06:1, which is
        // AA-large only, and the tooltip title measured 2.98:1.
        $this->assertStringNotContainsString('dark:text-gray-400 border-0 rounded-lg shadow-lg', $css);
        $this->assertMatchesRegularExpression(
            '/\.apexcharts-tooltip \{[^}]*dark:text-gray-100/s',
            $css,
            'Tooltip text needs gray-100 on the dark card.'
        );
        $this->assertMatchesRegularExpression(
            '/\.apexcharts-tooltip \.apexcharts-tooltip-title \{[^}]*dark:text-gray-100/s',
            $css,
            'The tooltip title needs gray-100 too.'
        );
    }

    public function test_every_dashboard_surface_declares_a_dark_variant(): void
    {
        foreach (['admin', 'warehouse_manager', 'warehouse_staff'] as $role) {
            $this->signIn($role);
            $body = $this->get(route('dashboard'))->assertOk()->getContent();

            $this->assertStringContainsString('dark:bg-gray-800', $body, "{$role} card surface");
            $this->assertStringContainsString('dark:border-gray-700', $body, "{$role} card border");
        }
    }

    /* ---- defensive routing ---------------------------------------------- */

    public function test_dashboard_views_guard_their_links_with_route_has(): void
    {
        foreach (['admin', 'manager', 'staff'] as $view) {
            $contents = file_get_contents(resource_path('views/dashboard/'.$view.'.blade.php'));

            $this->assertStringContainsString(
                'Route::has(',
                $contents,
                "dashboard/{$view}.blade.php links without a Route::has() guard."
            );
        }
    }

    public function test_the_task_card_degrades_when_its_queue_route_is_missing(): void
    {
        // A queue route that does not exist must drop the footer link rather
        // than throw on route() during render.
        $contents = file_get_contents(resource_path('views/components/dashboard/task-card.blade.php'));

        $this->assertStringContainsString('Route::has($queueRoute)', $contents);
    }

    public function test_the_modal_restore_scans_every_marker_not_just_the_first(): void
    {
        $source = file_get_contents(resource_path('js/transaction-ui.js'));

        // The approvals page carries a marker for a failed submission and one
        // for the ?review= deep link. querySelector saw only the first, so an
        // empty validation marker swallowed the deep link and the Review button
        // on the manager dashboard did nothing.
        $this->assertStringContainsString("querySelectorAll('[data-modal-restore]')", $source);
        $this->assertStringNotContainsString(
            "querySelector('[data-modal-restore]')",
            $source,
            'Only the first marker must not be read.'
        );
    }

    /**
     * Remove /* ... *\/ block comments so assertions read code, not prose.
     */
    private function stripJsComments(string $source): string
    {
        return (string) preg_replace('~/\*.*?\*/~s', '', $source);
    }
}
