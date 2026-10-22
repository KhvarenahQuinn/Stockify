<?php

namespace App\Support;

/**
 * Spreadsheet output hardening.
 *
 * PhpSpreadsheet guesses the cell type from the value. A user-controlled string
 * beginning with "=", "+", "-", "@", a tab or a carriage return can therefore be
 * treated as a formula by Excel or LibreOffice. Prefixing it with an apostrophe
 * keeps the value as text and stops CSV/Excel formula injection.
 */
final class Spreadsheet
{
    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    public static function row(array $values): array
    {
        return array_map(self::text(...), $values);
    }

    public static function text(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/u', $value) === 1
            ? "'".$value
            : $value;
    }
}
