<?php

if (! function_exists('format_number')) {
    /**
     * Format a number for the active interface language.
     *
     * English keeps Laravel/PHP's default separators (1,234.56), while
     * Indonesian swaps them (1.234,56). Calls that pass explicit separators,
     * such as exports that must stay machine-readable, are left untouched.
     */
    function format_number(
        int|float $value,
        int $decimals = 0,
        ?string $decimalSeparator = null,
        ?string $thousandsSeparator = null
    ): string {
        if ($decimalSeparator !== null || $thousandsSeparator !== null) {
            return number_format($value, $decimals, $decimalSeparator, $thousandsSeparator);
        }

        return app()->getLocale() === 'id'
            ? number_format($value, $decimals, ',', '.')
            : number_format($value, $decimals);
    }
}
