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
 * The master data screens are readable by warehouse roles but only writable by
 * an administrator. These tests pin both halves of that: the manager sees no
 * way to mutate anything, and the admin sees every control.
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
    public function test_manager_gets_a_read_only_view_of_every_master_data_page(string $routeName, string $createLabel): void
    {
        $this->seedMasterData();
        $this->signInAs('warehouse_manager');

        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertSee('Read-only.')
            ->assertDontSee($createLabel)
            // The edit and delete triggers are the only route into the CUD modals.
            ->assertDontSee('data-modal-toggle="edit-', escape: false)
            ->assertDontSee('data-modal-toggle="delete-', escape: false)
            ->assertDontSee('name="_method" value="DELETE"', escape: false);
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
     * @dataProvider masterDataPages
     */
    public function test_manager_is_refused_every_write_endpoint(string $routeName): void
    {
        $this->seedMasterData();
        $this->signInAs('warehouse_manager');

        $category = Category::first();
        $supplier = Supplier::first();
        $attribute = Attribute::first();
        $value = AttributeValue::first();

        $this->post(route('categories.store'), ['name' => 'Injected'])->assertForbidden();
        $this->put(route('categories.update', $category), ['name' => 'Injected'])->assertForbidden();
        $this->delete(route('categories.destroy', $category))->assertForbidden();

        $this->post(route('suppliers.store'), ['name' => 'Injected'])->assertForbidden();
        $this->put(route('suppliers.update', $supplier), ['name' => 'Injected'])->assertForbidden();
        $this->delete(route('suppliers.destroy', $supplier))->assertForbidden();

        $this->post(route('attributes.store'), ['name' => 'Injected'])->assertForbidden();
        $this->put(route('attributes.update', $attribute), ['name' => 'Injected'])->assertForbidden();
        $this->delete(route('attributes.destroy', $attribute))->assertForbidden();
        $this->post(route('attributes.values.store', $attribute), ['value' => 'Injected'])->assertForbidden();
        $this->delete(route('attributes.values.destroy', $value))->assertForbidden();

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('suppliers', 1);
        $this->assertDatabaseCount('attributes', 1);
        $this->assertDatabaseCount('attribute_values', 1);
    }

    public function test_staff_cannot_reach_master_data_at_all(): void
    {
        $this->signInAs('warehouse_staff');

        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('suppliers.index'))->assertForbidden();
        $this->get(route('attributes.index'))->assertForbidden();
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