<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The LIKE wildcard escaping must behave identically on MySQL/MariaDB (the
 * runtime engine) and SQLite (the default test engine). SQLite has no default
 * LIKE escape character, so the escapes only work when ESCAPE is stated.
 */
class LikeWildcardEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_action_prefix_treats_underscore_as_a_literal(): void
    {
        ActivityLog::create(['user_id' => $this->user->id, 'action' => 'user.created', 'description' => 'literal', 'ip_address' => '127.0.0.1']);
        ActivityLog::create(['user_id' => $this->user->id, 'action' => 'userxcreated', 'description' => 'wildcard', 'ip_address' => '127.0.0.1']);

        $matched = ActivityLog::query()->action('user.created')->pluck('action')->all();

        $this->assertSame(['user.created'], $matched, "'_' must match literally, not as a single-char wildcard");
    }

    public function test_product_search_treats_percent_and_underscore_as_literals(): void
    {
        $category = Category::create(['name' => 'Cotton', 'slug' => 'cotton']);
        $supplier = Supplier::create(['code' => 'ACME', 'name' => 'Acme', 'email' => 'a@e.x', 'phone' => '1', 'address' => 'x']);

        Product::create(['sku' => 'A-1_2', 'name' => '100% Cotton', 'category_id' => $category->id, 'supplier_id' => $supplier->id, 'buy_price' => 10, 'sell_price' => 12, 'unit' => 'pcs', 'current_stock' => 5, 'min_stock' => 1]);
        Product::create(['sku' => 'A-1X2', 'name' => '100 Cotton', 'category_id' => $category->id, 'supplier_id' => $supplier->id, 'buy_price' => 10, 'sell_price' => 12, 'unit' => 'pcs', 'current_stock' => 5, 'min_stock' => 1]);

        $this->assertSame(['100% Cotton'], Product::query()->search('100% Cotton')->pluck('name')->all(), "'%' must match literally");
        $this->assertSame(['A-1_2'], Product::query()->search('A-1_2')->pluck('sku')->all(), "'_' must match literally");
    }

    public function test_activity_report_description_search_escapes_percent(): void
    {
        ActivityLog::create(['user_id' => $this->user->id, 'action' => 'stock.adjusted', 'description' => 'Wrote off 50% of stock', 'ip_address' => '127.0.0.1']);
        ActivityLog::create(['user_id' => $this->user->id, 'action' => 'stock.adjusted', 'description' => 'Wrote off 50 units of stock', 'ip_address' => '127.0.0.1']);

        $report = app(ReportService::class)->activityLogReport(['search' => '50% of stock']);

        $this->assertSame(['Wrote off 50% of stock'], $report['rows']->pluck('description')->all());
    }
}
