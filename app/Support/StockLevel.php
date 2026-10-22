<?php

namespace App\Support;

/**
 * One definition of "is this product in trouble", used by the badge component,
 * the movement combobox and the JavaScript that repaints it after a pick.
 *
 * It lives in PHP rather than inside the Blade component because two runtimes
 * need the same answer: the badge renders server-side on a page load, and the
 * combobox swaps the badge client-side the moment a product is chosen. When the
 * two disagreed — which they did — the combobox showed "Out of stock" next to a
 * line reading "210 box on hand".
 */
final class StockLevel
{
    public const OUT = 'out';

    public const LOW = 'low';

    public const SAFE = 'safe';

    /**
     * Solid fills with white text.
     *
     * amber-700 rather than amber-600: white on #D97706 is 3.2:1, which clears
     * the 3:1 floor for large text and fails the 4.5:1 a 12px label needs.
     * #B45309 measures 5.0:1 and keeps the same visual weight as the green
     * (5.0:1) and red (6.5:1) beside it.
     *
     * @var array<string, array{label: string, tone: string}>
     */
    public const TONES = [
        self::OUT => [
            'label' => 'Out of stock',
            'tone' => 'bg-red-700 text-white',
        ],
        self::LOW => [
            'label' => 'Low stock',
            'tone' => 'bg-amber-700 text-white',
        ],
        self::SAFE => [
            'label' => 'Safe stock',
            'tone' => 'bg-green-700 text-white',
        ],
    ];

    /**
     * Out of stock wins over low stock, so a product with a min_stock of 0
     * still reads as empty rather than healthy.
     */
    public static function of(int $stock, int $minStock): string
    {
        if ($stock <= 0) {
            return self::OUT;
        }

        return $stock <= $minStock ? self::LOW : self::SAFE;
    }

    /**
     * The labels resolved through the translator.
     *
     * Class constants cannot call functions, so the English labels stay in
     * TONES and this method is the one runtime path that localises them.
     *
     * @return array<string, array{label: string, tone: string}>
     */
    public static function tones(): array
    {
        return collect(self::TONES)
            ->map(fn (array $tone) => ['label' => __($tone['label']), 'tone' => $tone['tone']])
            ->all();
    }

    /**
     * @return array{label: string, tone: string}
     */
    public static function describe(int $stock, int $minStock): array
    {
        return self::tones()[self::of($stock, $minStock)];
    }
}