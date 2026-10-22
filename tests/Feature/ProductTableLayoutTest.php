<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Products table proportions and the shared pagination view.
 *
 * The column minimums have to be declared on the header *and* the body cells;
 * declaring them in only one of the two lets the browser collapse a column
 * again, which is what these assertions exist to prevent.
 */
class ProductTableLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsManager(): void
    {
        $role = Role::create(['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager']);

        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => 'active']));
    }

    private function seedProduct(string $name = 'Corrugated Carton Box'): void
    {
        $category = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::create([
            'code' => 'SUP-0001', 'name' => 'Nusantara Packaging', 'email' => 'a@b.test',
            'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1',
        ]);

        \App\Models\Product::create([
            'sku' => 'SKU-PACK-001', 'name' => $name, 'description' => 'Seeded item.',
            'category_id' => $category->id, 'supplier_id' => $supplier->id,
            'buy_price' => 7200, 'sell_price' => 9500,
            'min_stock' => 60, 'current_stock' => 118, 'unit' => 'piece',
        ]);
    }

    public function test_header_cells_declare_their_column_minimums(): void
    {
        $this->signInAsManager();
        $this->seedProduct();

        $head = $this->productsHead();

        foreach ([
            'w-1/4 min-w-[14rem]',
            'min-w-[7rem]',
            'min-w-[9rem]',
            'min-w-[6.5rem]',
            'min-w-[8.5rem]',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $head,
                "Header is missing the column constraint \"{$expected}\"."
            );
        }
    }

    public function test_body_cells_repeat_the_header_column_minimums(): void
    {
        $this->signInAsManager();
        $this->seedProduct();

        $body = $this->productsBody();

        foreach ([
            'w-1/4 min-w-[14rem]',
            'min-w-[7rem] whitespace-nowrap',
            'min-w-[9rem]',
            'min-w-[6.5rem] whitespace-nowrap',
            'min-w-[8.5rem] whitespace-nowrap',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $body,
                "Body cells are missing the column constraint \"{$expected}\"."
            );
        }
    }

    public function test_prices_and_stock_do_not_wrap(): void
    {
        $this->signInAsManager();
        $this->seedProduct();

        $head = $this->productsHead();

        // SKU, Buy, Sell and Stock headers all carry whitespace-nowrap.
        $this->assertGreaterThanOrEqual(
            4,
            substr_count($head, 'whitespace-nowrap'),
            'SKU, both prices and the stock column must not wrap.'
        );
    }

    public function test_stock_count_and_badge_sit_side_by_side(): void
    {
        $this->signInAsManager();
        $this->seedProduct();

        $body = $this->productsBody();

        $this->assertStringContainsString(
            'inline-flex items-center justify-center gap-2',
            $body,
            'Stock count and badge should read as one inline figure.'
        );
        // The old stacked layout is what made the column need extra height.
        $this->assertStringNotContainsString('flex flex-col items-center gap-1.5', $body);
    }

    public function test_long_product_titles_truncate_against_the_column_not_a_fixed_cap(): void
    {
        $this->signInAsManager();
        $this->seedProduct(str_repeat('Very Long Product Title ', 6));

        $body = $this->productsBody();

        $this->assertStringContainsString('max-w-full truncate', $body, 'Title must truncate to the column width.');
        $this->assertStringNotContainsString('max-w-[14rem] truncate', $body, 'A fixed cap fights the column width.');
    }

    public function test_the_actions_column_stays_sticky_with_an_opaque_header(): void
    {
        $this->signInAsManager();
        $this->seedProduct();

        $head = $this->productsHead();

        $this->assertMatchesRegularExpression(
            '/<th[^>]*sticky right-0[^>]*bg-gray-50[^>]*dark:bg-gray-700[^>]*>/',
            $head,
            'Actions header must stay pinned with an opaque background in both modes.'
        );
    }

    public function test_the_pagination_view_is_published_and_marks_the_active_page(): void
    {
        $published = resource_path('views/vendor/pagination/tailwind.blade.php');

        $this->assertFileExists($published, 'The Tailwind pagination view must be published to override the default.');

        $view = file_get_contents($published);

        foreach ([
            'bg-primary-600',
            'text-white',
            'font-bold',
            'border-primary-600',
            'dark:bg-primary-600',
        ] as $token) {
            $this->assertStringContainsString($token, $view, "Active pagination state is missing \"{$token}\".");
        }

        // The active page must still be announced to assistive tech.
        $this->assertStringContainsString('aria-current="page"', $view);
    }

    public function test_a_paginated_page_renders_the_active_page_as_a_filled_chip(): void
    {
        $this->signInAsManager();

        $category = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::create([
            'code' => 'SUP-0001', 'name' => 'Nusantara Packaging', 'email' => 'a@b.test',
            'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1',
        ]);

        // More than one page so the paginator actually renders.
        foreach (range(1, 20) as $i) {
            \App\Models\Product::create([
                'sku' => 'SKU-PACK-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Item '.$i,
                'category_id' => $category->id, 'supplier_id' => $supplier->id,
                'buy_price' => 1000, 'sell_price' => 1500,
                'min_stock' => 10, 'current_stock' => 50, 'unit' => 'piece',
            ]);
        }

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-current="page"', $body, 'Paginated list must mark the current page.');

        // Assert on the active chip itself rather than the whole page, and
        // check each token independently so Tailwind's class order cannot
        // make this test brittle.
        $this->assertMatchesRegularExpression(
            '/<span aria-current="page">\s*<span class="([^"]*)"/',
            $body,
            'The current page must be rendered as a distinct chip.'
        );

        preg_match('/<span aria-current="page">\s*<span class="([^"]*)"/', $body, $active);

        foreach (['bg-primary-600', 'text-white', 'font-bold', 'border-primary-600'] as $token) {
            $this->assertStringContainsString(
                $token,
                $active[1] ?? '',
                "Active pagination chip is missing \"{$token}\"."
            );
        }
    }

    private function productsHead(): string
    {
        $body = $this->get(route('products.index'))->getContent();

        preg_match('/<thead.*?<\/thead>/s', $body, $m);

        return $m[0] ?? '';
    }

    private function productsBody(): string
    {
        $body = $this->get(route('products.index'))->getContent();

        preg_match('/<tbody.*?<\/tbody>/s', $body, $m);

        return $m[0] ?? '';
    }
}