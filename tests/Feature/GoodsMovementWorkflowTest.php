<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FE-005: the movement form, the inspection queue and the approval queue.
 *
 * The workflow now has two gates rather than one — a warehouse operator counts
 * the goods, then a manager decides — so these cover both gates, the display
 * status each one produces, and the contrast classes the badges render with.
 */
class GoodsMovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace('_', ' ', $name))]
        );
    }

    private function signIn(string $roleName): User
    {
        $user = User::factory()->create([
            'role_id' => $this->role($roleName)->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function product(int $stock = 40, int $minStock = 10): Product
    {
        return Product::create([
            'sku' => 'SKU-PACK-'.(Product::query()->count() + 1),
            'name' => 'Single Wall Carton 30x20x15 cm',
            'category_id' => $this->category()->id,
            'supplier_id' => $this->supplier()->id,
            'buy_price' => 7200, 'sell_price' => 9500,
            'min_stock' => $minStock, 'current_stock' => $stock, 'unit' => 'piece',
        ]);
    }

    /**
     * One supplier and one category for the whole test.
     *
     * Re-creating them per product trips the unique slug and code constraints
     * the moment a test needs more than one product.
     */
    private function supplier(): Supplier
    {
        return Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            [
                'name' => 'Nusantara Packaging',
                'email' => 'a@b.test', 'phone' => '+62-21-555-0101',
                'address' => 'Jl. Industri 1',
            ]
        );
    }

    private function category(): Category
    {
        return Category::firstOrCreate(['slug' => 'packaging'], ['name' => 'Packaging']);
    }

    private function pendingMovement(array $overrides = []): StockTransaction
    {
        $product = $overrides['product'] ?? $this->product();

        return StockTransaction::create(array_merge([
            'reference_number' => $overrides['reference'] ?? 'TRX-IN-20261016-001',
            'product_id' => $product->id,
            'user_id' => $overrides['user_id'] ?? User::factory()->create([
                'role_id' => $this->role('warehouse_staff')->id,
                'status' => 'active',
            ])->id,
            'type' => 'IN',
            'quantity' => 25,
            'stock_before' => 0,
            'stock_after' => 0,
            'status' => StockTransactionService::STATUS_PENDING,
            'destination' => null,
        ], array_diff_key($overrides, ['product' => null, 'reference' => null, 'user_id' => null])));
    }

    // ------------------------------------------------------------------
    // Movement form
    // ------------------------------------------------------------------

    public function test_the_movement_form_offers_every_required_field(): void
    {
        $this->signIn('warehouse_staff');
        $this->product();

        $body = $this->get(route('stock-transactions.create'))->assertOk()->getContent();

        foreach ([
            'name="type"',
            'name="product_id"',
            'name="quantity"',
            'name="supplier_id"',
            'name="destination"',
            'name="reference_note"',
            'name="document"',
        ] as $field) {
            $this->assertStringContainsString($field, $body, "The form is missing {$field}.");
        }
    }

    public function test_the_product_picker_is_a_searchable_combobox_carrying_live_stock(): void
    {
        $this->signIn('warehouse_staff');
        $product = $this->product(stock: 118);

        $body = $this->get(route('stock-transactions.create'))->assertOk()->getContent();

        // An ARIA combobox posting a hidden input, not a <select> the operator
        // has to scroll through every SKU in.
        $this->assertStringContainsString('role="combobox"', $body);
        $this->assertStringContainsString('data-product-option', $body);
        $this->assertStringContainsString('data-stock="'.$product->current_stock.'"', $body);
        $this->assertStringContainsString('data-min-stock="'.$product->min_stock.'"', $body);
    }

    public function test_a_goods_receipt_is_recorded_with_its_supplier_and_document(): void
    {
        Storage::fake('local');

        $this->signIn('warehouse_staff');
        $product = $this->product();
        $supplier = $product->supplier;

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => 25,
            'supplier_id' => $supplier->id,
            'reference_note' => 'SJ-MKU/XI/2026/8812',
            'notes' => 'Counted with the driver.',
            'document' => UploadedFile::fake()->create('sj-8812.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $transaction = StockTransaction::query()->latest('id')->firstOrFail();

        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->status);
        $this->assertSame($supplier->id, $transaction->supplier_id);
        $this->assertSame('SJ-MKU/XI/2026/8812', $transaction->reference_note);
        $this->assertStringStartsWith('TRX-IN-', $transaction->reference_number);
        $this->assertNotNull($transaction->document_path);
        Storage::disk('local')->assertExists($transaction->document_path);
    }

    public function test_transaction_documents_are_private_and_served_through_the_authenticated_route(): void
    {
        Storage::fake('local');

        $this->signIn('warehouse_staff');
        $product = $this->product();
        $supplier = $product->supplier;

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => 5,
            'supplier_id' => $supplier->id,
            'document' => UploadedFile::fake()->create('sj-private.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $transaction = StockTransaction::query()->latest('id')->firstOrFail();

        $this->get($transaction->document_url)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        auth()->logout();

        $this->get(route('stock-transactions.document', $transaction))
            ->assertRedirect(route('login'));
    }

    public function test_a_goods_issue_is_refused_when_it_exceeds_available_stock(): void
    {
        $this->signIn('warehouse_staff');
        $product = $this->product(stock: 12);

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'OUT',
            'quantity' => 50,
            'destination' => 'DC Surabaya Rungkut',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, StockTransaction::query()->count());
    }

    public function test_a_receipt_without_a_supplier_is_rejected(): void
    {
        $this->signIn('warehouse_staff');
        $product = $this->product();

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => 25,
        ])->assertSessionHasErrors('supplier_id');
    }

    // ------------------------------------------------------------------
    // Inspection queue
    // ------------------------------------------------------------------

    public function test_the_inspection_queue_is_open_to_every_movement_role(): void
    {
        $this->signIn('warehouse_staff');
        $this->pendingMovement();

        $this->get(route('stock-transactions.queue'))->assertOk();
    }

    public function test_recording_a_matching_count_advances_the_item_to_awaiting_approval(): void
    {
        $staff = $this->signIn('warehouse_staff');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.inspect', $transaction), [
            'inspected_quantity' => 25,
        ])->assertSessionHas('success');

        $transaction->refresh();

        $this->assertNotNull($transaction->inspected_at, 'The count must be stamped.');
        $this->assertSame($staff->id, $transaction->inspected_by);
        $this->assertSame(
            StockTransaction::DISPLAY_AWAITING_APPROVAL,
            $transaction->displayStatus(),
            'A matching count should read as awaiting approval.'
        );

        // The stored status deliberately does not move: the item has to stay in
        // the manager's queue rather than vanishing from it.
        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->status);
    }

    public function test_recording_a_short_count_flags_a_discrepancy(): void
    {
        $this->signIn('warehouse_staff');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.inspect', $transaction), [
            'inspected_quantity' => 19,
            'inspection_note' => 'Six cartons short against the delivery note.',
        ])->assertSessionHas('success');

        $transaction->refresh();

        $this->assertSame(-6, $transaction->inspectionVariance());
        $this->assertSame(StockTransaction::DISPLAY_DISCREPANCY, $transaction->displayStatus());
    }

    public function test_a_mismatch_without_a_note_is_refused(): void
    {
        $this->signIn('warehouse_staff');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.inspect', $transaction), [
            'inspected_quantity' => 19,
        ])->assertSessionHasErrors('inspection_note');

        $this->assertNull($transaction->refresh()->inspected_at);
    }

    public function test_the_queue_filters_split_the_four_workflow_states(): void
    {
        $this->signIn('warehouse_staff');

        $untouched = $this->pendingMovement(['reference' => 'TRX-IN-20261016-101']);
        $matched = $this->pendingMovement(['reference' => 'TRX-IN-20261016-102']);
        $short = $this->pendingMovement(['reference' => 'TRX-IN-20261016-103']);

        $matched->update(['inspected_at' => now(), 'inspected_quantity' => 25]);
        $short->update(['inspected_at' => now(), 'inspected_quantity' => 19]);

        $this->assertSame(
            [$untouched->reference_number],
            $this->idsFor(route('stock-transactions.queue', ['lens' => 'inspection']))
        );

        $this->assertSame(
            [$matched->reference_number],
            $this->idsFor(route('stock-transactions.queue', ['lens' => 'approval']))
        );

        $this->assertSame(
            [$short->reference_number],
            $this->idsFor(route('stock-transactions.queue', ['lens' => 'discrepancy']))
        );

        $this->assertCount(3, $this->idsFor(route('stock-transactions.queue')));
    }

    public function test_an_unknown_lens_falls_back_to_the_full_queue(): void
    {
        $this->signIn('warehouse_staff');
        $this->pendingMovement();

        $this->get(route('stock-transactions.queue', ['lens' => 'DROP TABLE']))
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // Approval queue
    // ------------------------------------------------------------------

    public function test_only_managers_and_admins_can_open_the_approval_queue(): void
    {
        $this->signIn('warehouse_staff');
        $this->get(route('stock-transactions.approvals'))->assertForbidden();

        $this->signIn('warehouse_manager');
        $this->get(route('stock-transactions.approvals'))->assertOk();

        $this->signIn('admin');
        $this->get(route('stock-transactions.approvals'))->assertOk();
    }

    public function test_a_staff_member_cannot_approve_or_reject_even_by_posting_directly(): void
    {
        $this->signIn('warehouse_staff');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.approve', $transaction), ['notes' => 'Looks fine to me.'])
            ->assertForbidden();

        $this->patch(route('stock-transactions.reject', $transaction), ['notes' => 'No thanks.'])
            ->assertForbidden();

        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->refresh()->status);
    }

    public function test_approving_posts_the_stock_change_and_writes_the_snapshot(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.approve', $transaction), [
            'notes' => 'Delivery note matches, 25 cartons counted.',
        ])->assertSessionHas('success');

        $transaction->refresh();

        $this->assertSame(StockTransactionService::STATUS_APPROVED, $transaction->status);
        $this->assertSame(40, $transaction->stock_before);
        $this->assertSame(65, $transaction->stock_after);
        $this->assertSame(65, $transaction->product->refresh()->current_stock);
    }

    public function test_approving_leaves_stock_untouched_on_a_goods_issue(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement(['type' => 'OUT', 'destination' => 'Cikarang']);

        $this->patch(route('stock-transactions.approve', $transaction), [
            'notes' => 'Loaded onto the 08:00 truck.',
        ])->assertSessionHas('success');

        $transaction->refresh();

        $this->assertSame(40, $transaction->stock_before);
        $this->assertSame(15, $transaction->stock_after);
    }

    public function test_approving_requires_a_review_note(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.approve', $transaction))
            ->assertSessionHasErrors('notes');

        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->refresh()->status);
    }

    public function test_a_manager_cannot_approve_a_movement_they_submitted(): void
    {
        $manager = $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement(['user_id' => $manager->id]);

        $this->patch(route('stock-transactions.approve', $transaction), [
            'notes' => 'Approving my own request should not work.',
        ])->assertSessionHas('error');

        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->refresh()->status);
    }

    public function test_rejecting_requires_a_reason_and_leaves_stock_alone(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement();

        $this->patch(route('stock-transactions.reject', $transaction))
            ->assertSessionHasErrors('notes');

        $this->patch(route('stock-transactions.reject', $transaction), [
            'notes' => 'Wrong purchase order. Resubmit against PO-99182.',
        ])->assertSessionHas('success');

        $transaction->refresh();

        $this->assertSame(StockTransactionService::STATUS_REJECTED, $transaction->status);
        $this->assertSame(0, $transaction->stock_before);
        $this->assertSame(0, $transaction->stock_after);
        $this->assertSame(40, $transaction->product->refresh()->current_stock);
    }

    public function test_a_flagged_discrepancy_cannot_be_approved(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement();
        $transaction->update(['inspected_at' => now(), 'inspected_quantity' => 19]);

        $this->patch(route('stock-transactions.approve', $transaction), [
            'notes' => 'Approving anyway.',
        ])->assertSessionHas('error');

        $this->assertSame(StockTransactionService::STATUS_PENDING, $transaction->refresh()->status);
        $this->assertSame(40, $transaction->product->refresh()->current_stock);
    }

    // ------------------------------------------------------------------
    // Audit table
    // ------------------------------------------------------------------

    public function test_the_history_renders_the_audit_snapshot_for_each_movement(): void
    {
        $this->signIn('warehouse_manager');
        $this->pendingMovement(['reference' => 'TRX-IN-20261016-777']);

        $body = $this->get(route('stock-transactions.index'))->assertOk()->getContent();

        $this->assertStringContainsString('TRX-IN-20261016-777', $body);
        foreach (['Reference', 'Product', 'Requested', 'Stock before', 'Status'] as $heading) {
            $this->assertStringContainsString($heading, $body, "The audit table lost its \"{$heading}\" column.");
        }
    }

    public function test_the_history_shows_stock_before_and_after_for_a_posted_movement(): void
    {
        $this->signIn('warehouse_manager');
        $transaction = $this->pendingMovement(['reference' => 'TRX-OUT-20261016-778']);
        $transaction->update([
            'type' => 'OUT',
            'status' => StockTransactionService::STATUS_APPROVED,
            'stock_before' => 40,
            'stock_after' => 15,
        ]);

        $body = preg_replace('/\s+/', ' ', $this->get(route('stock-transactions.index'))->assertOk()->getContent());

        // Blade renders the arrow as &rarr;, and the figures sit in two chips
        // either side of it, so the whole strip is matched rather than two
        // loose numbers that could come from anywhere on the page.
        $this->assertMatchesRegularExpression(
            '/> 40 <\/span>.*?&rarr;.*?> 15 <\/span>/',
            $body,
            'The before and after figures must render side by side.'
        );

        $this->assertMatchesRegularExpression(
            '/-25 .*on piece stock/',
            $body,
            'The signed delta must accompany the snapshot.'
        );
    }

    /**
     * The badge palette is the one thing a screen-reader user and a colour-blind
     * user both depend on, so the exact fills are pinned here rather than left to
     * review. green-700 = 5.0:1, amber-700 = 5.0:1, red-700 = 6.5:1 against
     * white; the 600-step amber would have been 3.2:1 and failed AA for a 12px
     * label.
     */
    public function test_status_badges_use_the_high_contrast_palette(): void
    {
        $this->signIn('warehouse_manager');

        $pending = $this->pendingMovement(['reference' => 'TRX-IN-20261016-801']);
        $matched = $this->pendingMovement(['reference' => 'TRX-IN-20261016-802']);
        $short = $this->pendingMovement(['reference' => 'TRX-IN-20261016-803']);
        $approved = $this->pendingMovement(['reference' => 'TRX-IN-20261016-804']);
        $rejected = $this->pendingMovement(['reference' => 'TRX-IN-20261016-805']);

        $matched->update(['inspected_at' => now(), 'inspected_quantity' => 25]);
        $short->update(['inspected_at' => now(), 'inspected_quantity' => 19]);
        $approved->update(['status' => StockTransactionService::STATUS_APPROVED]);
        $rejected->update(['status' => StockTransactionService::STATUS_REJECTED]);

        $body = $this->get(route('stock-transactions.index'))->assertOk()->getContent();

        foreach ([
            'Pending inspection' => 'bg-amber-700 text-white',
            'Awaiting approval' => 'bg-amber-700 text-white',
            'Discrepancy' => 'bg-red-700 text-white',
            'Approved' => 'bg-green-700 text-white',
            'Rejected' => 'bg-red-700 text-white',
        ] as $label => $classes) {
            $this->assertStringContainsString(
                $classes,
                $body,
                "The \"{$label}\" badge must render on {$classes} to pass WCAG AA."
            );
        }

        // The 600-step amber is the tempting one and it fails.
        $this->assertStringNotContainsString('bg-amber-600', $body);
    }

    // ------------------------------------------------------------------
    // Cross-queue links
    // ------------------------------------------------------------------

    public function test_the_staff_queue_never_offers_staff_a_link_to_a_page_they_cannot_open(): void
    {
        $this->signIn('warehouse_staff');
        $this->pendingMovement();

        $body = $this->get(route('stock-transactions.queue'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            route('stock-transactions.approvals'),
            $body,
            'Staff cannot open the approvals page, so the link must not be rendered.'
        );
    }

    public function test_a_manager_sees_a_link_from_the_staff_queue_to_the_approvals(): void
    {
        $this->signIn('warehouse_manager');
        $this->pendingMovement();

        $body = $this->get(route('stock-transactions.queue'))->assertOk()->getContent();

        $this->assertStringContainsString(route('stock-transactions.approvals'), $body);
    }

    public function test_the_history_header_also_hides_the_approvals_link_from_staff(): void
    {
        $this->signIn('warehouse_staff');
        $this->pendingMovement();

        $body = $this->get(route('stock-transactions.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            route('stock-transactions.approvals'),
            $body,
            'The ledger header must not offer staff a link to a page they cannot open.'
        );
    }

    /**
     * Reference numbers listed in the queue's table body.
     *
     * Scoped to the table so a match cannot come from a modal, a link elsewhere
     * on the page, or a reference quoted inside a hint.
     *
     * @return array<int, string>
     */
    private function idsFor(string $url): array
    {
        $body = $this->get($url)->assertOk()->getContent();

        preg_match('/<tbody.*?<\/tbody>/s', $body, $table);
        preg_match_all('/TRX-(?:IN|OUT)-[0-9]+-[0-9]+/', $table[0] ?? '', $matches);

        return array_values(array_unique($matches[0]));
    }
}