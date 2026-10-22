<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockOpnameSheet;
use App\Models\StockOpnameSheetItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FE-006: stock opname audit sheets.
 *
 * The load-bearing rule is that system_stock and variance are never taken from
 * the request. Everything else here guards the parts of the sheet that a forged
 * payload would otherwise be able to lie about.
 */
class StockOpnameSheetTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'warehouse_staff'): User
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

    private function product(string $sku, int $stock): Product
    {
        $category = Category::firstOrCreate(['name' => 'Packaging'], ['slug' => 'packaging']);
        $supplier = Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            ['name' => 'Nusantara', 'email' => 'a@b.test', 'phone' => '+62-21-555-0101', 'address' => 'Jl. Industri 1']
        );

        return Product::create([
            'sku' => $sku,
            'name' => 'Item '.$sku,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'buy_price' => 1000,
            'sell_price' => 1500,
            'min_stock' => 10,
            'current_stock' => $stock,
            'unit' => 'piece',
        ]);
    }

    /**
     * @param  array<int, array{product_id:int, physical_stock:int, reason?:string}>  $items
     */
    private function submit(array $items, ?User $auditor = null, array $extra = [])
    {
        return $this->post(route('stock-opnames.store'), array_merge([
            'items' => $items,
            'auditor_id' => ($auditor ?? $this->actingAsRole())->id,
        ], $extra));
    }

    /* ---- access -------------------------------------------------------- */

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('stock-opnames.index'))->assertRedirect(route('login'));
        $this->get(route('stock-opnames.create'))->assertRedirect(route('login'));
        $this->post(route('stock-opnames.store'), [])->assertRedirect(route('login'));
    }

    /**
     * @dataProvider countingRoles
     */
    public function test_counting_roles_can_open_the_sheet(string $role): void
    {
        $this->actingAsRole($role);
        $this->product('SKU-1', 10);

        $this->get(route('stock-opnames.create'))->assertOk();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function countingRoles(): array
    {
        return [
            'staff' => ['warehouse_staff'],
            'manager' => ['warehouse_manager'],
            'admin' => ['admin'],
        ];
    }

    /* ---- the sheet ----------------------------------------------------- */

    public function test_the_count_sheet_renders_the_building_blocks(): void
    {
        $this->actingAsRole();
        $this->product('SKU-1', 42);

        $body = $this->get(route('stock-opnames.create'))->assertOk()->getContent();

        // A row template the script clones, so the markup has one definition.
        $this->assertStringContainsString('data-opname-row-template', $body);

        // The audit sheet's own hooks must all be present.
        foreach (['data-opname-sheet', 'data-opname-form', 'data-opname-rows', 'data-opname-search', 'data-opname-category', 'data-opname-results', 'data-opname-alert'] as $hook) {
            $this->assertStringContainsString($hook, $body, "Missing hook {$hook}.");
        }

        // Live summary tiles, one per metric the brief calls for.
        foreach (['items', 'matched', 'deficit', 'surplus'] as $metric) {
            $this->assertStringContainsString('data-summary-metric="'.$metric.'"', $body);
        }

        // Three badge variants, server-rendered so the script owns no class.
        foreach (['matched', 'surplus', 'deficit'] as $kind) {
            $this->assertStringContainsString('data-opname-badge-template="'.$kind.'"', $body);
        }
    }

    public function test_the_confirm_dialog_lists_the_discrepancy_breakdown(): void
    {
        $this->actingAsRole();
        $this->product('SKU-1', 10);

        $body = $this->get(route('stock-opnames.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="confirm-opname-modal"', $body);
        $this->assertStringContainsString('id="confirm-opname-breakdown"', $body);
        $this->assertStringContainsString('data-opname-confirm-submit', $body);
        $this->assertStringContainsString('data-opname-confirm-cancel', $body);

        // The acknowledgement the brief requires.
        $this->assertStringContainsString('official stock balance', $body);

        // The shell caps the body height, so a long breakdown scrolls rather
        // than running off the viewport.
        $this->assertStringContainsString('max-h-[70vh] overflow-y-auto', $body);
    }

    /* ---- variance maths ------------------------------------------------ */

    public function test_submitting_records_the_count_with_derived_variance(): void
    {
        $user = $this->actingAsRole();
        $exact = $this->product('SKU-1', 50);
        $short = $this->product('SKU-2', 20);
        $over = $this->product('SKU-3', 10);

        $this->submit([
            ['product_id' => $exact->id, 'physical_stock' => 50, 'reason' => null],
            ['product_id' => $short->id, 'physical_stock' => 17, 'reason' => 'Breakage in transit'],
            ['product_id' => $over->id, 'physical_stock' => 14, 'reason' => 'Received extra carton'],
        ], $user)->assertRedirect();

        $sheet = StockOpnameSheet::query()->firstOrFail();

        $this->assertSame('SUBMITTED', $sheet->status);
        $this->assertSame(3, $sheet->totalItems());
        $this->assertSame(1, $sheet->totalMatched());
        $this->assertSame(3, $sheet->totalDeficit(), '17 counted against 20 is a deficit of 3.');
        $this->assertSame(4, $sheet->totalSurplus(), '14 counted against 10 is a surplus of 4.');
        $this->assertSame(1, $sheet->totalNetVariance(), 'Net is 4 over minus 3 short.');

        $this->assertDatabaseHas('stock_opname_sheet_items', [
            'product_id' => $short->id,
            'system_stock' => 20,
            'physical_stock' => 17,
            'variance' => -3,
            'reason' => 'Breakage in transit',
        ]);
    }

    public function test_system_stock_cannot_be_forged_by_the_client(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        // The client claims system_stock was 500 and that the variance is -490.
        $this->post(route('stock-opnames.store'), [
            'items' => [[
                'product_id' => $product->id,
                'physical_stock' => 50,
                'system_stock' => 500,
                'variance' => -490,
            ]],
            'auditor_id' => $user->id,
        ])->assertRedirect();

        // The server re-read the product and derived zero variance instead.
        $this->assertDatabaseHas('stock_opname_sheet_items', [
            'product_id' => $product->id,
            'system_stock' => 50,
            'variance' => 0,
        ]);

        $this->assertSame(1, StockOpnameSheet::query()->firstOrFail()->totalMatched());
    }

    public function test_submitting_reconciles_the_official_stock_balance(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        $this->submit([['product_id' => $product->id, 'physical_stock' => 46]], $user)->assertRedirect();

        $this->assertSame(46, $product->fresh()->current_stock);
    }

    public function test_the_reference_follows_the_sop_yyyymmdd_xxx_format(): void
    {
        $user = $this->actingAsRole();
        $this->product('SKU-1', 10);

        $this->submit([['product_id' => Product::first()->id, 'physical_stock' => 10]], $user)->assertRedirect();

        $reference = StockOpnameSheet::query()->firstOrFail()->reference;

        $this->assertMatchesRegularExpression('/^SOP-\d{8}-\d{3}$/', $reference);
    }

    public function test_references_increment_within_a_day(): void
    {
        $user = $this->actingAsRole();
        $this->product('SKU-1', 10);

        $this->submit([['product_id' => Product::first()->id, 'physical_stock' => 10]], $user);
        $this->submit([['product_id' => Product::first()->id, 'physical_stock' => 11]], $user);

        $references = StockOpnameSheet::query()->orderBy('id')->pluck('reference')->all();

        $this->assertCount(2, $references);
        $this->assertNotSame($references[0], $references[1]);
        $this->assertStringEndsWith('-001', $references[0]);
        $this->assertStringEndsWith('-002', $references[1]);
    }

    /* ---- validation ---------------------------------------------------- */

    public function test_a_sheet_needs_at_least_one_line(): void
    {
        $user = $this->actingAsRole();

        $this->from(route('stock-opnames.create'))
            ->submit([], $user)
            ->assertRedirect(route('stock-opnames.create'))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, StockOpnameSheet::query()->count());
    }

    public function test_counting_the_same_product_twice_is_rejected(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 10);

        $this->from(route('stock-opnames.create'))
            ->submit([
                ['product_id' => $product->id, 'physical_stock' => 10],
                ['product_id' => $product->id, 'physical_stock' => 12],
            ], $user)
            ->assertRedirect(route('stock-opnames.create'))
            ->assertSessionHasErrors('items.0.product_id');

        $this->assertSame(0, StockOpnameSheet::query()->count());
    }

    public function test_a_negative_count_is_rejected(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 10);

        $this->from(route('stock-opnames.create'))
            ->submit([['product_id' => $product->id, 'physical_stock' => -1]], $user)
            ->assertSessionHasErrors('items.0.physical_stock');
    }

    public function test_a_count_must_be_whole(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 10);

        $this->from(route('stock-opnames.create'))
            ->submit([['product_id' => $product->id, 'physical_stock' => 'ten']], $user)
            ->assertSessionHasErrors('items.0.physical_stock');
    }

    public function test_an_auditor_must_be_named(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 10);

        $this->from(route('stock-opnames.create'))
            ->post(route('stock-opnames.store'), [
                'items' => [['product_id' => $product->id, 'physical_stock' => 10]],
            ])
            ->assertSessionHasErrors('auditor_id');
    }

    public function test_a_rejected_submission_repopulates_the_counted_lines(): void
    {
        $user = $this->actingAsRole();
        $product = $this->product('SKU-1', 10);

        $this->from(route('stock-opnames.create'))
            ->submit([['product_id' => $product->id, 'physical_stock' => -3]], $user)
            ->assertSessionHasErrors('items.0.physical_stock');

        $body = $this->get(route('stock-opnames.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-opname-restore="[{', $body);
        $this->assertStringContainsString((string) $product->id, $body);
    }

    /* ---- history ------------------------------------------------------- */

    private function seedSheet(string $reference, string $date, User $auditor, array $lines): StockOpnameSheet
    {
        $sheet = StockOpnameSheet::create([
            'reference' => $reference,
            'auditor_id' => $auditor->id,
            'audit_date' => $date,
            'status' => StockOpnameSheet::STATUS_SUBMITTED,
        ]);

        foreach ($lines as [$product, $physical]) {
            StockOpnameSheetItem::create([
                'stock_opname_sheet_id' => $sheet->id,
                'product_id' => $product->id,
                'system_stock' => $product->current_stock,
                'physical_stock' => $physical,
                'variance' => $physical - $product->current_stock,
            ]);
        }

        return $sheet->fresh();
    }

    public function test_the_history_table_lists_reference_date_auditor_and_totals(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);
        $this->seedSheet('SOP-20261015-001', '2026-10-15', $auditor, [[$product, 47]]);

        $body = $this->get(route('stock-opnames.index'))->assertOk()->getContent();

        $this->assertStringContainsString('SOP-20261015-001', $body);
        $this->assertStringContainsString('15 Oct 2026', $body);
        $this->assertStringContainsString($auditor->name, $body);
        $this->assertStringContainsString('-3', $body, 'the net variance is shown');
        $this->assertStringContainsString('Reconciled', $body);
        $this->assertStringContainsString('View details', $body);
    }

    public function test_the_history_filters_by_date_range(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        $this->seedSheet('SOP-20261001-001', '2026-10-01', $auditor, [[$product, 50]]);
        $this->seedSheet('SOP-20261020-001', '2026-10-20', $auditor, [[$product, 50]]);

        $this->get(route('stock-opnames.index', ['from' => '2026-10-10', 'to' => '2026-10-25']))
            ->assertOk()
            ->assertSee('SOP-20261020-001')
            ->assertDontSee('SOP-20261001-001');
    }

    public function test_the_history_filters_by_auditor(): void
    {
        $mine = $this->actingAsRole('warehouse_staff');
        $otherRole = Role::firstOrCreate(['name' => 'warehouse_manager'], ['display_name' => 'Warehouse Manager']);
        $theirs = User::factory()->create(['role_id' => $otherRole->id, 'status' => 'active']);

        $product = $this->product('SKU-1', 50);
        $this->seedSheet('SOP-20261001-001', '2026-10-01', $mine, [[$product, 50]]);
        $this->seedSheet('SOP-20261002-001', '2026-10-02', $theirs, [[$product, 50]]);

        $this->get(route('stock-opnames.index', ['auditor_id' => $theirs->id]))
            ->assertOk()
            ->assertSee('SOP-20261002-001')
            ->assertDontSee('SOP-20261001-001');
    }

    public function test_the_history_filters_to_discrepancies_only(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        $this->seedSheet('SOP-20261001-001', '2026-10-01', $auditor, [[$product, 50]]);
        $this->seedSheet('SOP-20261002-001', '2026-10-02', $auditor, [[$product, 44]]);

        $this->get(route('stock-opnames.index', ['variance_status' => 'discrepancy']))
            ->assertOk()
            ->assertSee('SOP-20261002-001')
            ->assertDontSee('SOP-20261001-001');
    }

    public function test_the_history_filters_to_matched_only(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        $this->seedSheet('SOP-20261001-001', '2026-10-01', $auditor, [[$product, 50]]);
        $this->seedSheet('SOP-20261002-001', '2026-10-02', $auditor, [[$product, 44]]);

        $this->get(route('stock-opnames.index', ['variance_status' => 'matched']))
            ->assertOk()
            ->assertSee('SOP-20261001-001')
            ->assertDontSee('SOP-20261002-001');
    }

    public function test_the_history_paginates(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);

        for ($i = 1; $i <= 18; $i++) {
            $this->seedSheet(sprintf('SOP-202610%02d-%03d', 1, $i), '2026-10-01', $auditor, [[$product, 50]]);
        }

        // Ordering is audit_date desc then id desc, and all 18 share a date, so the
        // newest ids land first: page 1 holds 018 down to 004.
        $page = $this->get(route('stock-opnames.index'))->assertOk();

        $page->assertSee('SOP-20261001-018');
        $page->assertDontSee('SOP-20261001-003', false);

        $this->get(route('stock-opnames.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('SOP-20261001-003')
            ->assertDontSee('SOP-20261001-018', false);
    }

    public function test_an_empty_history_says_so(): void
    {
        $this->actingAsRole();

        $this->get(route('stock-opnames.index'))
            ->assertOk()
            ->assertSee('No stock opnames recorded yet.');
    }

    /* ---- detail -------------------------------------------------------- */

    public function test_the_detail_page_shows_every_line_with_its_badge(): void
    {
        $auditor = $this->actingAsRole();
        $a = $this->product('SKU-1', 50);
        $b = $this->product('SKU-2', 20);

        $sheet = $this->seedSheet('SOP-20261017-001', '2026-10-17', $auditor, [[$a, 50], [$b, 16]]);

        $body = $this->get(route('stock-opnames.show', $sheet))->assertOk()->getContent();

        $this->assertStringContainsString('SOP-20261017-001', $body);
        $this->assertStringContainsString('Item SKU-1', $body);
        $this->assertStringContainsString('Item SKU-2', $body);
        // The deficit line is badged in words, not colour alone.
        $this->assertStringContainsString('short', $body);
    }

    /* ---- accessibility & design ---------------------------------------- */

    public function test_the_variance_badges_use_the_aa_fills(): void
    {
        $this->actingAsRole();

        $component = file_get_contents(resource_path('views/components/opnames/variance-badge.blade.php'));

        // Measured on white with this project's Flowbite palette:
        // green-700 6.44:1, blue-700 6.18:1, red-700 5.74:1.
        foreach (['bg-green-700 text-white', 'bg-blue-700 text-white', 'bg-red-700 text-white'] as $tone) {
            $this->assertStringContainsString($tone, $component);
        }

        // Every chip names its state so the meaning survives greyscale.
        $this->assertStringContainsString('Match', $component);
        $this->assertStringContainsString('found', $component);
        $this->assertStringContainsString('short', $component);
    }

    public function test_every_new_surface_declares_a_dark_variant(): void
    {
        $auditor = $this->actingAsRole();
        $product = $this->product('SKU-1', 50);
        $sheet = $this->seedSheet('SOP-20261017-001', '2026-10-17', $auditor, [[$product, 47]]);

        foreach ([route('stock-opnames.create'), route('stock-opnames.index'), route('stock-opnames.show', $sheet)] as $url) {
            $body = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('dark:', $body, "{$url} has no dark variants at all.");
            $this->assertStringContainsString('dark:bg-gray-800', $body, "{$url} is missing the dark surface.");
        }
    }

    public function test_the_semantic_fills_live_only_in_blade(): void
    {
        $source = file_get_contents(resource_path('js/opname-ui.js'));

        // The three meaning-carrying fills carry the AA contrast guarantee, so
        // they must exist in exactly one place. The script clones the Blade
        // templates instead of restating them.
        foreach (['bg-green-700', 'bg-red-700', 'bg-blue-700'] as $fill) {
            $this->assertStringNotContainsString($fill, $source, "{$fill} must not be restated in the script.");
        }

        $this->assertStringContainsString('data-opname-badge-template', $source);
        $this->assertStringContainsString('data-variance-slot', $source);
    }

    public function test_the_script_ships_the_live_calculation(): void
    {
        $source = file_get_contents(resource_path('js/opname-ui.js'));

        foreach ([
            'data-physical-input',
            'recalcRow',
            'recalcSummary',
            'data-summary-metric',
            'data-summary-value',
            'data-opname-row-template',
            // read through the dataset, so the attribute name is camelCase here
            'opnameRestore',
        ] as $hook) {
            $this->assertStringContainsString($hook, $source, "The script is missing {$hook}.");
        }

        // Every summary tile is written on each recalculation, from one loop over the
        // rows rather than a listener per tile.
        $this->assertStringContainsString("set('items', items)", $source);
        $this->assertStringContainsString("set('matched', matched)", $source);
        $this->assertStringContainsString("set('deficit', deficit)", $source);
        $this->assertStringContainsString("set('surplus', surplus)", $source);
    }

    public function test_everything_the_script_looks_up_by_id_exists(): void
    {
        $this->actingAsRole();

        $body = $this->get(route('stock-opnames.create'))->assertOk()->getContent();
        $source = file_get_contents(resource_path('js/opname-ui.js'));

        // The script addresses these by id, so a data attribute of the same name
        // is not enough: getElementById returns null and the confirm dialog
        // opens with empty totals.
        preg_match_all("/getElementById\('([\w-]+)'\)/", $source, $matches);

        $this->assertNotEmpty($matches[1], 'The script should look up at least one id.');

        foreach (array_unique($matches[1]) as $id) {
            $this->assertStringContainsString(
                'id="'.$id.'"',
                $body,
                "The script asks for #{$id} but no element carries it."
            );
        }
    }

    public function test_a_deficit_is_shown_with_its_sign(): void
    {
        $source = file_get_contents(resource_path('js/opname-ui.js'));

        // A shortfall of six rendered as "6" in a Difference column reads as a
        // surplus of six, so the sign has to survive formatting.
        $this->assertStringContainsString('const signed =', $source);
        $this->assertSame(
            2,
            substr_count($source, 'signed('),
            'The badge figure and the confirm-table cell both go through signed().'
        );
    }

    public function test_the_script_is_wired_into_the_bundle(): void
    {
        $this->assertStringContainsString(
            "import './opname-ui';",
            file_get_contents(resource_path('js/app.js'))
        );
    }

    public function test_staff_cannot_file_a_count_under_another_auditor(): void
    {
        $this->actingAsRole('warehouse_staff');

        $other = User::factory()->create([
            'role_id' => Role::where('name', 'warehouse_staff')->value('id'),
            'status' => 'active',
        ]);
        $product = $this->product('SKU-SPOOF', 10);

        $this->post(route('stock-opnames.store'), [
            'items' => [['product_id' => $product->id, 'physical_stock' => 9]],
            'auditor_id' => $other->id,
        ])->assertSessionHasErrors('auditor_id');

        $this->assertDatabaseCount('stock_opname_sheets', 0);
        $this->assertSame(10, (int) $product->fresh()->current_stock);
    }

    public function test_a_manager_can_file_a_count_on_behalf_of_a_counter(): void
    {
        $this->actingAsRole('warehouse_manager');

        $counter = User::factory()->create([
            'role_id' => Role::firstOrCreate(
                ['name' => 'warehouse_staff'],
                ['display_name' => 'Warehouse Staff']
            )->id,
            'status' => 'active',
        ]);
        $product = $this->product('SKU-ON-BEHALF', 10);

        $this->post(route('stock-opnames.store'), [
            'items' => [['product_id' => $product->id, 'physical_stock' => 9]],
            'auditor_id' => $counter->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stock_opname_sheets', ['auditor_id' => $counter->id]);
    }
}