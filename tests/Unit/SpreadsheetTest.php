<?php

namespace Tests\Unit;

use App\Support\Spreadsheet;
use PHPUnit\Framework\TestCase;

class SpreadsheetTest extends TestCase
{
    public function test_formula_like_strings_are_made_literal(): void
    {
        foreach (['=1+1', '+SUM(A1)', '-2+3', '@SUM(A1)', "\tcmd", "\r=1+1"] as $value) {
            $this->assertSame("'{$value}", Spreadsheet::text($value));
        }
    }

    public function test_normal_strings_and_non_strings_are_unchanged(): void
    {
        $this->assertSame('Carton 30x20', Spreadsheet::text('Carton 30x20'));
        $this->assertSame('', Spreadsheet::text(''));
        $this->assertSame(12, Spreadsheet::text(12));
        $this->assertNull(Spreadsheet::text(null));
    }

    public function test_a_row_is_sanitized_without_losing_numeric_cells(): void
    {
        $this->assertSame(
            ["'=cmd", 'Safe', 12, null],
            Spreadsheet::row(['=cmd', 'Safe', 12, null])
        );
    }
}
