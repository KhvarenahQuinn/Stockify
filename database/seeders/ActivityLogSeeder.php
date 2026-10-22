<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Audit trail for the dashboard activity card and the activity report.
 *
 * Timestamps are backdated so the feed reads like a real history. A share of
 * entries have a null user_id because that column is nullable precisely for
 * system-initiated actions, and the report renders those as "system".
 *
 * The table carries no unique constraint, so rows are keyed on
 * (action, description, user_id) via updateOrCreate. That keeps a re-seed from
 * quietly doubling the feed, which a plain insert() would do every time.
 *
 * Must run after UserSeeder.
 */
class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            throw new \RuntimeException('ActivityLogSeeder needs seeded users.');
        }

        $templates = [
            ['product.created', 'Created product %s'],
            ['product.updated', 'Updated product %s'],
            ['product.deleted', 'Deleted product %s'],
            ['category.created', 'Created category Packaging Materials'],
            ['category.updated', 'Renamed a category'],
            ['supplier.created', 'Added supplier Nusantara Packaging'],
            ['supplier.updated', 'Updated supplier contact details'],
            ['stock.adjusted', 'Wrote off 12 damaged units'],
            ['stock.opname.verified', 'Verified stock count variance'],
            ['user.updated', 'Updated an account status'],
            ['settings.updated', 'Updated default low stock threshold'],
            ['import.completed', 'Imported 18 products from spreadsheet'],
        ];

        $items = [
            'Corrugated Carton 60x40x30 cm',
            'Stretch Wrap 500 mm',
            'Handheld Barcode Scanner',
            'Floor Cleaner Concentrate 5 L',
            'Insulated Pallet Cover',
            'Gel Pack Medium',
        ];

        for ($i = 0; $i < 45; $i++) {
            [$action, $template] = $templates[$i % count($templates)];

            $user = $users[$i % $users->count()];

            // Roughly one in six entries is system-initiated.
            $isSystem = $i % 6 === 0;

            $createdAt = now()->subHours($i * 9 + random_int(0, 5));

            ActivityLog::updateOrCreate(
                [
                    'action' => $action,
                    'description' => sprintf($template, $items[$i % count($items)]),
                    'user_id' => $isSystem ? null : $user->id,
                ],
                [
                    'ip_address' => $isSystem ? '127.0.0.1' : '10.20.'.random_int(1, 4).'.'.random_int(10, 250),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }
}