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
 * The per-attribute autocomplete tag selector on the product form.
 *
 * The interaction is JavaScript; these assertions pin the hooks the script
 * depends on and the structural rules that keep the markup valid when the
 * component renders once per modal.
 */
class AttributeSearchTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogue(int $products = 1): void
    {
        $role = Role::create(['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => 'active']));

        $category = Category::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $supplier = Supplier::create([
            'code' => 'SUP-0001', 'name' => 'Nusantara', 'email' => 'a@b.test',
            'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1',
        ]);

        $colour = Attribute::create(['name' => 'Colour']);
        foreach (['Red', 'Blue', 'Natural'] as $value) {
            AttributeValue::create(['attribute_id' => $colour->id, 'value' => $value]);
        }

        $size = Attribute::create(['name' => 'Size']);
        foreach (['Small', 'Large'] as $value) {
            AttributeValue::create(['attribute_id' => $size->id, 'value' => $value]);
        }

        for ($i = 1; $i <= $products; $i++) {
            $product = \App\Models\Product::create([
                'sku' => 'SKU-PACK-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Item '.$i,
                'category_id' => $category->id, 'supplier_id' => $supplier->id,
                'buy_price' => 1000, 'sell_price' => 1500,
                'min_stock' => 10, 'current_stock' => 50, 'unit' => 'piece',
            ]);

            if ($i === 1) {
                $product->attributeValues()->sync([$colour->values()->where('value', 'Red')->first()->id]);
            }
        }
    }

    public function test_each_attribute_gets_its_own_search_field(): void
    {
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        // Placeholder names the attribute, which is the whole point of per-attribute search.
        $this->assertStringContainsString('placeholder="Search Colour..."', $body);
        $this->assertStringContainsString('placeholder="Search Size..."', $body);

        preg_match_all('/data-attribute-group="(\d+)"/', $body, $groups);
        $this->assertGreaterThan(1, count(array_unique($groups[1])), 'Both attributes must render a group.');
    }

    public function test_values_are_not_rendered_as_an_expanded_checklist(): void
    {
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        // One checkbox per chip, not per available value.
        $this->assertSame(1, substr_count($body, 'data-attribute-chip-input'), 'Only chosen values may post a checkbox.');

        // Values live in the dropdown as buttons, and the dropdown starts closed.
        $this->assertStringContainsString('data-attribute-dropdown', $body);
        $this->assertStringContainsString('data-attribute-option', $body);
        $this->assertStringContainsString('role="combobox"', $body);
        $this->assertStringContainsString('aria-expanded="false"', $body);
    }

    public function test_each_search_field_has_a_unique_id_bound_to_its_label(): void
    {
        $this->seedCatalogue(3);

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        // \s before id= so a data-* attribute ending in "-id" is not matched.
        preg_match_all('/\sid="(attr-[^"]*)"/', $body, $ids);

        $this->assertGreaterThan(1, count($ids[1]));
        $this->assertCount(count(array_unique($ids[1])), $ids[1], 'Combobox ids must be unique across the page.');

        // Both the combobox input and the listbox it controls are captured here;
        // only the inputs are labelled and wired to aria-controls.
        $inputs = array_values(array_filter(
            array_unique($ids[1]),
            fn (string $id) => ! str_ends_with($id, '-list')
        ));
        $lists = array_values(array_filter(
            array_unique($ids[1]),
            fn (string $id) => str_ends_with($id, '-list')
        ));

        $this->assertNotEmpty($inputs);
        $this->assertCount(count($inputs), $lists, 'Every combobox needs a matching listbox id.');

        foreach ($inputs as $id) {
            $this->assertStringContainsString('for="'.$id.'"', $body, "Combobox #{$id} has no bound label.");
            $this->assertStringContainsString('aria-controls="'.$id.'-list"', $body);
            $this->assertStringContainsString('id="'.$id.'-list"', $body);
        }
    }

    public function test_a_selected_value_renders_as_a_chip_carrying_its_checkbox(): void
    {
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-attribute-chip', $body, 'The pre-selected value must render as a chip.');
        $this->assertStringContainsString('data-attribute-chip-remove', $body, 'Every chip needs a remove button.');
        $this->assertStringContainsString('aria-label="Remove Red"', $body);
        $this->assertMatchesRegularExpression(
            '/name="attribute_value_ids\[\]"[^>]*value="\d+"[^>]*checked/s',
            $body,
            'The chip must carry a checked checkbox under the original field name.'
        );
    }

    public function test_the_value_list_area_stays_height_capped(): void
    {
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        $this->assertStringContainsString(
            'max-h-64 space-y-4 overflow-y-auto',
            $body,
            'Many chips must not stretch the modal.'
        );
    }

    public function test_each_dropdown_carries_a_no_match_row(): void
    {
        $this->seedCatalogue();

        $body = $this->get(route('products.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-attribute-no-match', $body);
        $this->assertStringContainsString('No matches', $body);
    }

    public function test_the_highlight_toggles_every_class_token_individually(): void
    {
        $source = file_get_contents(resource_path('js/product-ui.js'));

        // DOMTokenList.toggle() accepts (token, force) only. Spreading a token
        // list plus the flag honours the first token and silently drops the
        // rest, which applied a bare bg-gray-100 with no dark:bg-gray-700 to
        // counter it - light grey behind light text in dark mode.
        $this->assertStringNotContainsString(
            'classList.toggle(...',
            $source,
            'classList.toggle() takes a single token; spreading silently drops the others.'
        );

        $this->assertStringContainsString(
            'ACTIVE_OPTION_TOKENS.forEach',
            $source,
            'The highlight must apply its tokens one at a time.'
        );
    }

    public function test_the_option_button_base_class_list_has_no_background(): void
    {
        $source = file_get_contents(resource_path('js/product-ui.js'));

        // Grab the OPTION_CLASS literal.
        $this->assertSame(
            1,
            preg_match("/const OPTION_CLASS =\s*\n\s*'([^']+)'/", $source, $m),
            'OPTION_CLASS must be a single string literal.'
        );

        $tokens = preg_split('/\s+/', trim($m[1]));

        // No unconditional background: only hover:/focus: variants may tint it.
        $unprefixed = array_values(array_filter(
            $tokens,
            fn (string $t) => str_starts_with($t, 'bg-')
        ));

        $this->assertSame([], $unprefixed, 'Option buttons must be transparent at rest.');

        foreach (['hover:bg-gray-100', 'focus:bg-gray-100', 'dark:hover:bg-gray-700', 'dark:focus:bg-gray-700', 'dark:text-gray-200'] as $expected) {
            $this->assertContains($expected, $tokens, "Missing {$expected}.");
        }
    }

    public function test_the_script_ships_the_combobox_behaviour(): void
    {
        $source = file_get_contents(resource_path('js/product-ui.js'));

        foreach ([
            'data-attribute-chip',
            'data-attribute-chip-input',
            'data-attribute-chip-remove',
            'data-attribute-dropdown',
            'data-attribute-option',
            'addChip',
            'refresh',
        ] as $hook) {
            $this->assertStringContainsString($hook, $source, "The script is missing \"{$hook}\".");
        }

        // Closed on outside click and on Escape, as required.
        $this->assertStringContainsString('document.addEventListener(\'click\'', $source);
        $this->assertStringContainsString("event.key === 'Escape'", $source);

        // Enter must not submit the product form from inside the combobox.
        $this->assertStringContainsString('event.preventDefault();', $source);

        // A category change refreshes the options in place so chips survive.
        $this->assertStringContainsString("categorySelect?.addEventListener('change'", $source);
        $this->assertStringContainsString('rebuildOptions(group, attribute)', $source);
    }
}