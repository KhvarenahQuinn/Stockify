<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Stock movement history for the transaction ledger and the activity feed.
 *
 * Timestamps are backdated over roughly three weeks so the reports have a
 * spread to chart rather than a single day. stock_before/stock_after are derived
 * from the product's current level so the numbers stay internally consistent.
 *
 * reference_number is UNIQUE, and it is derived from the row's position in this
 * list rather than from a running total — a counter would produce different
 * numbers on every run and collide with the previous run's rows.
 *
 * Must run after UserSeeder and ProductSeeder.
 */
class StockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $staff = User::whereHas('role', fn ($q) => $q->where('name', 'warehouse_staff'))->get();
        $managers = User::whereHas('role', fn ($q) => $q->where('name', 'warehouse_manager'))->get();

        if ($products->isEmpty() || $staff->isEmpty()) {
            throw new \RuntimeException('StockTransactionSeeder needs seeded products and staff users.');
        }

        // 60 movements across three weeks: enough to paginate, not so many that
        // the seeder dominates the fixture runtime.
        for ($i = 0; $i < 60; $i++) {
            /** @var Product $product */
            $product = $products[$i % $products->count()];

            $type = $i % 2 === 0 ? 'IN' : 'OUT';
            $quantity = random_int(5, 90);
            $before = (int) $product->current_stock;
            $after = $type === 'IN' ? $before + $quantity : max(0, $before - $quantity);

            // Mostly settled, with a tail of pending work so the approval queue
            // is not empty on a fresh install.
            $status = $i % 7 === 0 ? 'PENDING' : ($i % 11 === 0 ? 'REJECTED' : 'APPROVED');

            $submittedBy = $staff[$i % $staff->count()];
            $reviewedBy = $status === 'PENDING' ? null : $managers[$i % $managers->count()];

            $createdAt = now()->subDays(random_int(0, 20))->subMinutes(random_int(0, 1400));

            StockTransaction::updateOrCreate(
                ['reference_number' => sprintf('TRX-%05d', $i + 1)],
                [
                    'product_id' => $product->id,
                    'user_id' => $submittedBy->id,
                    'approved_by' => $reviewedBy?->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'status' => $status,
                    'notes' => $status === 'REJECTED'
                        ? 'Damaged on arrival, returned to supplier.'
                        : $type.' recorded during scheduled goods movement.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }
}