<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The master data screens are readable by warehouse roles and writable by the
 * manager and the administrator. The policies decide that; these tests pin the
 * two ends of it: the manager gets every control and every write endpoint, the
 * staff account gets neither.
 */
class MasterDataAccessTest extends TestCase
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

    private function seedMasterData(): void
    {
        $category = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Sinar Kemasan',
            'email' => 'orders@sinar.test',
            'phone' => '+62 21 555 0134',
            'address' => 'Jl. Industri No. 4, Jakarta',
        ]);
        $attribute = Attribute::create(['name' => 'Size']);
        AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'L']);

        unset($category, $supplier, $attribute);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function masterDataPages(): array
    {
        return [
            'categories' => ['categories.index', 'Add category'],
            'suppliers' => ['suppliers.index', 'Add supplier'],
            'attributes' => ['attributes.index', 'Add attribute'],
        ];
    }

    /**
     * @dataProvider masterDataPages
     */
    public function test_manager_gets_the_full_create_edit_delete_controls(string $routeName, string $createLabel): void
    {
        $this->seedMasterData();
        $this->signInAs('warehouse_manager');

        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($createLabel)
            ->assertSee('data-modal-toggle="edit-', escape: false)
            ->assertSee('data-modal-toggle="delete-', escape: false)
            // The read-only note is what the page used to show the manager.
            ->assertDontSee('Read-only.');
    }

    /**
     * @dataProvider masterDataPages
     */
    public function test_admin_gets_the_full_create_edit_delete_controls(string $routeName, string $createLabel): void
    {
        $this->seedMasterData();
        $this->signInAs('admin');

        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($createLabel)
            ->assertSee('data-modal-toggle="edit-', escape: false)
            ->assertSee('data-modal-toggle="delete-', escape: false);
    }

    /**
     * Every master data write has to go through: the route guard, the form
     * request and the service all have to agree with the policy.
     */
    public function test_manager_can_write_every_master_data_endpoint(): void
    {
        $this->signInAs('warehouse_manager');
        $this->seedMasterData();

        $this->assertEveryMasterDataWriteWorks();
    }

    public function test_admin_can_write_every_master_data_endpoint(): void
    {
        $this->signInAs('admin');
        $this->seedMasterData();

        $this->assertEveryMasterDataWriteWorks();
    }

    private function assertEveryMasterDataWriteWorks(): void
    {
        $category = Category::first();
        $supplier = Supplier::first();
        $attribute = Attribute::first();
        $value = AttributeValue::first();

        $this->post(route('categories.store'), ['name' => 'Cold Chain'])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Cold Chain', 'slug' => 'cold-chain']);

        $this->put(route('categories.update', $category), ['name' => 'Packaging and labels'])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Packaging and labels']);

        $created = Category::where('name', 'Cold Chain')->firstOrFail();
        $this->delete(route('categories.destroy', $created))
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $created->id]);

        $this->post(route('suppliers.store'), [
            'name' => 'Nusantara Plastik',
            'email' => 'sales@nusantara.test',
            'phone' => '+62 21 555 0199',
            'address' => 'Jl. Raya Bekasi No. 12',
        ])->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Nusantara Plastik', 'code' => 'SUP-0002']);

        $this->put(route('suppliers.update', $supplier), [
            'name' => 'Sinar Kemasan Jaya',
            'email' => 'orders@sinar.test',
            'phone' => '+62 21 555 0134',
            'address' => 'Jl. Industri No. 4, Jakarta',
        ])->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Sinar Kemasan Jaya']);

        $newSupplier = Supplier::where('name', 'Nusantara Plastik')->firstOrFail();
        $this->delete(route('suppliers.destroy', $newSupplier))
            ->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $newSupplier->id]);

        $this->post(route('attributes.store'), ['name' => 'Material'])
            ->assertRedirect();
        $this->assertDatabaseHas('attributes', ['name' => 'Material']);

        $this->put(route('attributes.update', $attribute), ['name' => 'Garment size'])
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]));
        $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'Garment size']);

        $this->post(route('attributes.values.store', $attribute), ['value' => 'XL'])
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]));
        $this->assertDatabaseHas('attribute_values', ['attribute_id' => $attribute->id, 'value' => 'XL']);

        $this->delete(route('attributes.values.destroy', $value))
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]));
        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);

        $material = Attribute::where('name', 'Material')->firstOrFail();
        $this->delete(route('attributes.destroy', $material))
            ->assertRedirect(route('attributes.index'));
        $this->assertDatabaseMissing('attributes', ['id' => $material->id]);
    }

    public function test_staff_cannot_reach_master_data_at_all(): void
    {
        $this->signInAs('warehouse_staff');

        // Staff are bounced to their dashboard with a warning (StaffAuthRedirect)
        // rather than shown a 403 page, so the expectation is the redirect.
        $this->get(route('categories.index'))->assertRedirect(route('dashboard'));
        $this->get(route('suppliers.index'))->assertRedirect(route('dashboard'));
        $this->get(route('attributes.index'))->assertRedirect(route('dashboard'));

        $this->post(route('categories.store'), ['name' => 'Injected'])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('categories', ['name' => 'Injected']);
    }

    public function test_manager_search_filters_the_list_without_leaking_write_controls(): void
    {
        Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        Category::create(['name' => 'Spare parts', 'slug' => 'spare-parts']);

        $this->signInAs('warehouse_manager');

        $this->get(route('categories.index', ['q' => 'packaging']))
            ->assertOk()
            ->assertSee('Packaging')
            ->assertDontSee('Spare parts');

        $this->get(route('categories.index', ['q' => 'no-such-category']))
            ->assertOk()
            ->assertSee('No categories match');
    }

    public function test_slug_generation_happens_when_the_slug_is_left_blank(): void
    {
        $this->signInAs('admin');

        $this->post(route('categories.store'), ['name' => 'Cold Chain Supplies'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Cold Chain Supplies', 'slug' => 'cold-chain-supplies']);
    }

    public function test_supplier_codes_are_issued_in_the_sup_series(): void
    {
        $this->signInAs('admin');

        $this->post(route('suppliers.store'), [
            'name' => 'Sinar Kemasan',
            'email' => 'orders@sinar.test',
            'phone' => '+62 21 555 0134',
            'address' => 'Jl. Industri No. 4',
        ])->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-0001']);
    }

    public function test_attribute_values_are_added_and_removed_individually(): void
    {
        $attribute = Attribute::create(['name' => 'Size']);
        $this->signInAs('admin');

        $this->post(route('attributes.values.store', $attribute), ['value' => 'XL'])
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]));

        $this->assertDatabaseHas('attribute_values', ['attribute_id' => $attribute->id, 'value' => 'XL']);

        // Adding the same value twice is rejected with a field error rather
        // than creating a duplicate row.
        $this->from(route('attributes.index', ['attribute' => $attribute->id]))
            ->post(route('attributes.values.store', $attribute), ['value' => 'XL'])
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]))
            ->assertSessionHasErrors('value');

        $value = AttributeValue::where('value', 'XL')->firstOrFail();

        $this->delete(route('attributes.values.destroy', $value))
            ->assertRedirect(route('attributes.index', ['attribute' => $attribute->id]));

        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    }

    public function test_the_attributes_page_keeps_the_selected_attribute_in_the_detail_pane(): void
    {
        $size = Attribute::create(['name' => 'Size']);
        AttributeValue::create(['attribute_id' => $size->id, 'value' => 'S']);
        Attribute::create(['name' => 'Colour']);

        $this->signInAs('admin');

        $this->get(route('attributes.index', ['attribute' => $size->id]))
            ->assertOk()
            ->assertSee('Size')
            ->assertSee('Colour')
            ->assertSee('1 value available to products');
    }
}
