<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirming a destructive action.
 *
 * A browser `confirm()` cannot explain why a delete is refused, and the stock
 * ledger's RESTRICT keys refuse plenty of them, so every delete now goes
 * through a dialog instead.
 */
class DestructiveConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        Role::create(['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager']);
        Role::create(['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff']);

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);
    }

    private function makeUser(string $name, string $roleName = 'warehouse_staff'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@stockify.test',
            'role_id' => Role::where('name', $roleName)->value('id'),
            'status' => 'active',
        ]);
    }

    public function test_deleting_an_account_named_on_stock_records_is_refused_with_an_explanation(): void
    {
        $staff = $this->makeUser('Siti Rahayu');

        StockTransaction::factory()->create(['user_id' => $staff->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $staff))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', __('This account is named on 1 stock record. Deactivate it instead: deleting would orphan the history.'));

        // A 500 from the driver is what this guard replaced.
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
        $this->assertSame(0, ActivityLog::query()->where('action', 'user.deleted')->count());
    }

    public function test_the_index_dialog_explains_the_refusal_instead_of_offering_a_dead_end(): void
    {
        $staff = $this->makeUser('Siti Rahayu');

        StockTransaction::factory()->create(['user_id' => $staff->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('delete-user-'.$staff->id, false)
            ->assertSee('Delete is refused')
            ->assertSee('Deactivate user')
            // The safe alternative submits the toggle form already on the page.
            ->assertSee('form="toggle-user-'.$staff->id.'"', false)
            // No destructive submit when the delete cannot succeed.
            ->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_an_unused_account_gets_the_delete_form_in_the_dialog(): void
    {
        $staff = $this->makeUser('Budi Santoso');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('delete-user-'.$staff->id, false)
            ->assertSee('Deleting '.$staff->name.' ends their access for good. This cannot be undone.')
            ->assertSee('action="'.route('admin.users.destroy', $staff).'"', false)
            ->assertDontSee('Delete is refused');
    }

    public function test_the_user_detail_page_uses_the_same_dialog(): void
    {
        $staff = $this->makeUser('Ratna Kusuma');

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $staff))
            ->assertOk()
            ->assertSee('delete-user-'.$staff->id, false)
            ->assertSee('data-modal-target="delete-user-'.$staff->id.'"', false)
            ->assertDontSee('onsubmit="return confirm(', false)
            // The dialog's alternative action needs the status form to exist.
            ->assertSee('id="toggle-user-'.$staff->id.'"', false);
    }

    public function test_removing_an_attribute_value_opens_a_dialog_instead_of_a_browser_confirm(): void
    {
        $attribute = Attribute::create(['name' => 'Size']);

        $value = AttributeValue::create([
            'attribute_id' => $attribute->id,
            'value' => 'XL',
        ]);

        $this->actingAs($this->admin)
            ->get(route('attributes.index', ['attribute' => $attribute->id]))
            ->assertOk()
            ->assertSee('delete-value-'.$value->id, false)
            ->assertSee('Remove XL from Size?')
            ->assertDontSee('onsubmit="return confirm(', false);
    }
}
