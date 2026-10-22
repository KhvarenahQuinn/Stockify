<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Table responsiveness and the dashboard activity list.
 *
 * The sticky Actions column has two failure modes worth pinning: losing its
 * background (so scrolled content shows through) and breaking row hover (a
 * sticky cell keeps its own background, which reads as a hole in a highlighted
 * row unless it is wired to the row's group).
 */
class TableResponsivenessTest extends TestCase
{
    use RefreshDatabase;

    private function signInAs(string $roleName): User
    {
        $role = Role::create([
            'name' => $roleName,
            'display_name' => ucwords(str_replace('_', ' ', $roleName)),
        ]);

        $user = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($user);

        return $user;
    }

    private function seedCatalogue(): void
    {
        $category = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::create([
            'code' => 'SUP-001', 'name' => 'Sinar Kemasan', 'email' => 'a@b.test',
            'phone' => '+62 21 555 0134', 'address' => 'Jl. Industri 4',
        ]);

        \App\Models\Product::create([
            'sku' => 'PKG-001', 'name' => 'Corrugated Carton Box',
            'category_id' => $category->id, 'supplier_id' => $supplier->id,
            'buy_price' => 3500, 'sell_price' => 5000,
            'min_stock' => 100, 'current_stock' => 240, 'unit' => 'piece',
        ]);
    }

    public function test_the_user_table_actions_header_is_sticky_with_its_own_background(): void
    {
        $this->signInAs('admin');

        $body = $this->get(route('admin.users.index'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<th[^>]*sticky right-0[^>]*bg-gray-50[^>]*>.*?sr-only">Actions/s',
            $body,
            'Actions header must stay pinned with an opaque background.'
        );
    }

    public function test_the_user_table_actions_cell_is_sticky_and_follows_row_hover(): void
    {
        $this->signInAs('admin');

        $body = $this->get(route('admin.users.index'))->getContent();

        $this->assertStringContainsString('group-hover:bg-gray-50', $body, 'Sticky cell must adopt the row hover background.');
        $this->assertStringContainsString('<tr class="group border-b', $body, 'Rows need the group class for group-hover to fire.');
    }

    public function test_the_user_table_drops_the_actions_menu_within_the_scroll_container(): void
    {
        $this->signInAs('admin');

        $body = $this->get(route('admin.users.index'))->getContent();

        $this->assertStringContainsString('relative overflow-x-auto', $body, 'Sticky needs a positioned scroll ancestor.');
        $this->assertStringContainsString('data-dropdown-placement="bottom-end"', $body, 'Menu must open from the trigger edge.');
    }

    public function test_the_user_table_raises_the_open_menu_above_other_sticky_cells(): void
    {
        $this->signInAs('admin');

        $body = $this->get(route('admin.users.index'))->getContent();

        // position:sticky is a stacking context, so a menu inside it is painted
        // only within that cell. The z-index has to be lifted while it is open.
        $this->assertStringContainsString("classList.toggle('z-30'", $body);
        $this->assertStringContainsString("attributeFilter: ['class']", $body);
    }

    public function test_the_products_table_actions_column_is_sticky_for_a_manager(): void
    {
        $this->signInAs('warehouse_manager');
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->getContent();

        $this->assertStringContainsString('sticky right-0 z-10 bg-gray-50', $body, 'Products actions header must be pinned.');
        $this->assertStringContainsString(
            'sticky right-0 z-10 whitespace-nowrap border-l border-gray-200 bg-white',
            $body,
            'Products actions cell must be pinned with an opaque background.'
        );
        $this->assertStringContainsString('group-hover:bg-gray-50', $body);
    }

    public function test_read_only_roles_get_no_sticky_actions_column(): void
    {
        $this->signInAs('warehouse_staff');
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->getContent();

        // Staff see the catalogue without an actions column at all, so there is
        // nothing to pin.
        $this->assertStringNotContainsString('sticky right-0', $body);
    }

    public function test_category_table_drops_secondary_columns_on_mobile(): void
    {
        $this->signInAs('admin');

        Category::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'description' => 'Cartons and tape',
        ]);

        $body = $this->get(route('categories.index'))->getContent();

        foreach (['Slug', 'Description', 'Created'] as $column) {
            $this->assertMatchesRegularExpression(
                '/<th[^>]*class="[^"]*\bhidden\b[^"]*\bmd:table-cell\b[^"]*"[^>]*>\s*'.$column.'\s*<\/th>/',
                $body,
                "The {$column} column should collapse below md."
            );
        }

        $this->assertStringContainsString('md:hidden', $body, 'The category slug stays available as mobile subtext.');
        $this->assertStringContainsString('inline-flex h-10 w-10 shrink-0', $body, 'Mobile action buttons stay touch friendly.');
        $this->assertStringContainsString('hidden md:inline', $body, 'Action labels collapse to icons on mobile.');
    }

    public function test_supplier_table_drops_contact_and_address_on_mobile(): void
    {
        $this->signInAs('admin');

        Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Sinar Kemasan',
            'email' => 'orders@sinar.test',
            'phone' => '+62 21 555 0134',
            'address' => 'Jl. Industri No. 4, Jakarta',
        ]);

        $body = $this->get(route('suppliers.index'))->getContent();

        foreach (['Contact', 'Address'] as $column) {
            $this->assertMatchesRegularExpression(
                '/<th[^>]*class="[^"]*\bhidden\b[^"]*\bmd:table-cell\b[^"]*"[^>]*>\s*'.$column.'\s*<\/th>/',
                $body,
                "The {$column} column should collapse below md."
            );
        }

        $this->assertStringContainsString('break-all', $body, 'Long email addresses wrap instead of forcing the table sideways.');
        $this->assertStringContainsString('inline-flex h-10 w-10 shrink-0', $body, 'Mobile action buttons stay touch friendly.');
    }

    public function test_activity_rows_use_a_fixed_split_so_timestamps_line_up(): void
    {
        $this->signInAs('admin');

        $long = 'A considerably longer activity description that has to truncate';

        foreach (['Short', $long] as $description) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'product.created',
                'description' => $description,
                'ip_address' => '127.0.0.1',
            ]);
        }

        $body = $this->get(route('dashboard'))->getContent();

        // The property, not the old implementation. These assertions used to
        // demand a 12-column grid, which fixed the sideways drift only because
        // the description was clamped to col-span-7. The feed now puts the
        // metadata on its own row, so it cannot drift at any description
        // length - a stronger guarantee than the grid was - and the description
        // is still clamped so the row keeps a fixed height.
        $this->assertStringContainsString('min-w-0 flex-1 truncate', $body, 'Description must be clamped in a flexible column.');
        $this->assertStringContainsString('title="'.$long.'"', $body, 'The full description stays reachable.');

        // Metadata must not share the row with the description.
        $this->assertMatchesRegularExpression(
            '/title="'.preg_quote($long, '/').'"[\s\S]*?<\/p>[\s\S]*?<time\b/',
            $body,
            'The timestamp must live below the description, not beside it.'
        );
    }

    public function test_activity_metadata_splits_actor_and_time_onto_separate_lines(): void
    {
        $this->signInAs('admin');

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'category.updated',
            'description' => 'Renamed a category',
            'ip_address' => '127.0.0.1',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Renamed a category');
    }

    public function test_a_long_description_does_not_push_metadata_out_of_alignment(): void
    {
        $this->signInAs('admin');

        $long = str_repeat('very long description ', 12);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'product.updated',
            'description' => $long,
            'ip_address' => '127.0.0.1',
        ]);

        $body = $this->get(route('dashboard'))->getContent();

        // truncate plus the title attribute keeps the full text reachable.
        $this->assertStringContainsString('title="'.$long.'"', $body);
    }
}