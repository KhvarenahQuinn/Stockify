<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Product permissions follow .skills/WORKFLOW.md rather than "admins own
 * everything": the warehouse manager owns the catalogue, staff and admin are
 * read-only. These tests pin that split, because it is the opposite of the
 * master data screens and therefore easy to regress by accident.
 */
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function signInAs(string $roleName): User
    {
        $role = Role::create([
            'name' => $roleName,
            'display_name' => ucwords(str_replace('_', ' ', $roleName)),
        ]);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        return $user;
    }

    private function makeProduct(array $overrides = []): Product
    {
        $category = Category::first() ?? Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::first() ?? Supplier::create([
            'code' => 'SUP-001', 'name' => 'Sinar Kemasan', 'email' => 'a@b.test',
            'phone' => '+62 21 555 0134', 'address' => 'Jl. Industri 4',
        ]);

        return Product::create(array_merge([
            'sku' => 'PKG-001',
            'name' => 'Corrugated Carton Box',
            'description' => 'Single-wall carton.',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'buy_price' => 3500,
            'sell_price' => 5000,
            'min_stock' => 100,
            'current_stock' => 240,
            'unit' => 'piece',
        ], $overrides));
    }

    public function test_manager_reaches_every_product_write_endpoint(): void
    {
        $this->signInAs('warehouse_manager');
        $product = $this->makeProduct();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Add product')
            ->assertSee('Import')
            ->assertSee('data-modal-toggle="edit-', escape: false)
            ->assertSee('data-modal-toggle="delete-', escape: false);

        $this->post(route('products.store'), [
            'name' => 'Bubble Wrap Roll',
            'category_id' => $product->category_id,
            'supplier_id' => $product->supplier_id,
            'buy_price' => 10000,
            'sell_price' => 14000,
            'min_stock' => 20,
            'unit' => 'roll',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Bubble Wrap Roll']);
    }

    public function test_staff_gets_a_read_only_catalogue(): void
    {
        $this->signInAs('warehouse_staff');
        $this->makeProduct();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Read-only.')
            ->assertDontSee('Add product')
            ->assertDontSee('import-product-modal')
            ->assertDontSee('data-modal-toggle="edit-', escape: false)
            ->assertDontSee('data-modal-toggle="delete-', escape: false)
            ->assertDontSee('name="_method" value="DELETE"', escape: false);
    }

    /**
     * WORKFLOW.md puts product management outside the admin remit, so the admin
     * sees the catalogue but cannot change it. This is deliberate and the
     * inverse of the master data screens.
     */
    public function test_admin_can_read_the_catalogue_but_not_change_it(): void
    {
        $this->signInAs('admin');
        $this->makeProduct();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Read-only.')
            ->assertDontSee('Add product')
            ->assertDontSee('data-modal-toggle="edit-', escape: false);
    }

    /**
 * @dataProvider writeRefusedRoles
 */
    public function test_write_endpoints_are_refused_outside_the_manager_role(string $roleName): void
    {
        $this->signInAs($roleName);
        $product = $this->makeProduct();

        $this->post(route('products.store'), [])->assertForbidden();
        $this->put(route('products.update', $product), [])->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();
        $this->post(route('products.import'), [])->assertForbidden();
        $this->get(route('products.template'))->assertForbidden();

        $this->assertDatabaseCount('products', 1);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function writeRefusedRoles(): array
    {
        return [
            'staff' => ['warehouse_staff'],
            'admin' => ['admin'],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: string}>
     */
    public static function stockBadgeCases(): array
    {
        return [
            'safe' => [240, 100, 'bg-green-700'],
            'low' => [40, 100, 'bg-amber-700'],
            'at threshold' => [100, 100, 'bg-amber-700'],
            'out' => [0, 100, 'bg-red-700'],
        ];
    }

    /**
     * @dataProvider stockBadgeCases
     */
    public function test_stock_badge_matches_the_threshold(int $stock, int $min, string $expectedClass): void
    {
        $this->signInAs('warehouse_manager');
        $this->makeProduct(['sku' => 'X-'.$stock.'-'.$min, 'current_stock' => $stock, 'min_stock' => $min]);

        $this->get(route('products.index'))->assertOk()->assertSee($expectedClass, escape: false);
    }

    public function test_stock_filter_buckets_match_the_badge_rules(): void
    {
        $this->signInAs('warehouse_manager');
        $this->makeProduct(['sku' => 'SAFE-1', 'current_stock' => 240, 'min_stock' => 100]);
        $this->makeProduct(['sku' => 'LOW-1', 'current_stock' => 40, 'min_stock' => 100]);
        $this->makeProduct(['sku' => 'OUT-1', 'current_stock' => 0, 'min_stock' => 100]);

        $this->get(route('products.index', ['stock' => 'safe']))->assertOk()->assertSee('SAFE-1')->assertDontSee('LOW-1');
        $this->get(route('products.index', ['stock' => 'low']))->assertOk()->assertSee('LOW-1')->assertDontSee('OUT-1');
        $this->get(route('products.index', ['stock' => 'out']))->assertOk()->assertSee('OUT-1')->assertDontSee('SAFE-1');
    }

    public function test_an_unknown_stock_filter_falls_back_to_all(): void
    {
        $this->signInAs('warehouse_manager');
        $this->makeProduct(['sku' => 'SAFE-1', 'current_stock' => 240, 'min_stock' => 100]);
        $this->makeProduct(['sku' => 'OUT-1', 'current_stock' => 0, 'min_stock' => 100]);

        $this->get(route('products.index', ['stock' => 'wat']))
            ->assertOk()
            ->assertSee('SAFE-1')
            ->assertSee('OUT-1');
    }

    public function test_category_filter_and_search_narrow_the_list(): void
    {
        $this->signInAs('warehouse_manager');

        $packaging = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $hardware = Category::create(['name' => 'Hardware', 'slug' => 'hardware']);

        $this->makeProduct(['sku' => 'PKG-1', 'category_id' => $packaging->id]);
        $this->makeProduct(['sku' => 'HRD-1', 'name' => 'Hex Bolt M8', 'category_id' => $hardware->id]);

        $this->get(route('products.index', ['category_id' => $hardware->id]))
            ->assertOk()
            ->assertSee('HRD-1')
            ->assertDontSee('PKG-1');

        $this->get(route('products.index', ['q' => 'hex']))
            ->assertOk()
            ->assertSee('Hex Bolt M8')
            ->assertDontSee('PKG-1');
    }

    public function test_create_rejects_an_invalid_payload_and_reopens_the_modal(): void
    {
        $this->signInAs('warehouse_manager');
        $product = $this->makeProduct();

        $this->from(route('products.index'))
            ->post(route('products.store'), [
                '_form' => 'create-product-modal',
                'name' => '',
                'sell_price' => 'not-a-number',
            ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors(['name', 'category_id', 'supplier_id', 'buy_price', 'sell_price', 'min_stock', 'unit']);

        $this->assertDatabaseCount('products', 1);
        unset($product);
    }

    public function test_creating_a_product_stores_the_uploaded_image(): void
    {
        Storage::fake('public');

        $this->signInAs('warehouse_manager');
        $product = $this->makeProduct();

        $this->post(route('products.store'), [
            'name' => 'Bubble Wrap Roll',
            'category_id' => $product->category_id,
            'supplier_id' => $product->supplier_id,
            'buy_price' => 10000,
            'sell_price' => 14000,
            'min_stock' => 20,
            'current_stock' => 5,
            'unit' => 'roll',
            'image' => UploadedFile::fake()->image('wrap.png', 400, 400),
        ])->assertRedirect(route('products.index'));

        $created = Product::where('name', 'Bubble Wrap Roll')->firstOrFail();

        $this->assertNotNull($created->image);
        Storage::disk('public')->assertExists($created->image);
    }

    public function test_attribute_values_are_attached_to_a_new_product(): void
    {
        $this->signInAs('warehouse_manager');
        $product = $this->makeProduct();

        $attribute = Attribute::create(['name' => 'Size']);
        $value = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'L']);

        $this->post(route('products.store'), [
            'name' => 'Bubble Wrap Roll',
            'category_id' => $product->category_id,
            'supplier_id' => $product->supplier_id,
            'buy_price' => 10000,
            'sell_price' => 14000,
            'min_stock' => 20,
            'unit' => 'roll',
            'attribute_value_ids' => [$value->id],
        ])->assertRedirect(route('products.index'));

        $created = Product::where('name', 'Bubble Wrap Roll')->firstOrFail();

        $this->assertSame([$value->id], $created->attributeValues->pluck('id')->all());
    }

    public function test_editing_keeps_the_sku_when_the_field_is_cleared(): void
    {
        $this->signInAs('warehouse_manager');
        $product = $this->makeProduct(['sku' => 'PKG-KEEP']);

        $this->put(route('products.update', $product), [
            'sku' => '',
            'name' => 'Renamed Carton',
        ])->assertRedirect(route('products.index'));

        $this->assertSame('PKG-KEEP', $product->fresh()->sku);
        $this->assertSame('Renamed Carton', $product->fresh()->name);
    }

    public function test_the_import_endpoint_rejects_a_file_that_is_not_a_spreadsheet(): void
    {
        $this->signInAs('warehouse_manager');

        $this->from(route('products.index'))
            ->post(route('products.import'), [
                '_form' => 'import-product-modal',
                'file' => UploadedFile::fake()->create('payload.php', 4),
            ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('file');
    }

    public function test_the_import_endpoint_requires_a_file(): void
    {
        $this->signInAs('warehouse_manager');

        $this->from(route('products.index'))
            ->post(route('products.import'), ['_form' => 'import-product-modal'])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('file');
    }

    public function test_the_sample_template_downloads_with_the_import_headings(): void
    {
        $this->signInAs('warehouse_manager');

        $response = $this->get(route('products.template'));

        $response->assertOk();
        $response->assertDownload('product-import-template.xlsx');

        $path = $response->baseResponse->getFile()->getPathname();

        // Excel::toArray() wraps the sheet in an outer array, so the heading row
        // sits at [0][0].
        $sheet = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Exports\ProductTemplateExport(), $path)[0] ?? [];

        $this->assertNotEmpty($sheet, 'The template must be a readable workbook, not an empty stub.');

        // The headings have to match ProductImport::rules() or a downloaded
        // template will not import back in.
        $this->assertSame(array_keys((new \App\Imports\ProductImport())->rules()), $sheet[0]);

        // One worked example row, so the sheet is not a list of empty headers.
        $this->assertCount(2, $sheet);
        $this->assertSame('Corrugated Carton Box Large', $sheet[1][1]);
    }

    /**
     * A product with a stored image must render its thumbnail. This guards the
     * image-URL accessor: with every seeded image null, a broken accessor is
     * short-circuited away and never surfaces.
     */
    public function test_a_product_with_an_image_renders_its_thumbnail(): void
    {
        Storage::fake('public');

        $this->signInAs('warehouse_manager');

        $path = 'products/box.png';
        Storage::disk('public')->put($path, 'x');
        $this->makeProduct(['sku' => 'PKG-IMG', 'image' => $path]);

        $response = $this->get(route('products.index'));

        $response->assertOk();

        $url = Product::where('sku', 'PKG-IMG')->firstOrFail()->image_url;

        $this->assertNotNull($url, 'image_url must resolve a stored image to a public URL.');
        $response->assertSee($url, escape: false);
        // Decorative thumbnail: empty alt keeps it out of the screen-reader queue.
        $response->assertSee('alt="" loading="lazy"', escape: false);
    }

    public function test_deleting_a_product_removes_its_image(): void
    {
        Storage::fake('public');

        $this->signInAs('warehouse_manager');

        $path = 'products/box.png';
        Storage::disk('public')->put($path, 'x');
        $product = $this->makeProduct(['image' => $path]);

        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}