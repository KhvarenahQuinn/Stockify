<?php

namespace Tests\Unit;

use App\Support\ProductionSafety;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProductionSafetyTest extends TestCase
{
    public function test_production_with_debug_enabled_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);

        ProductionSafety::assertSafe('production', true);
    }

    public function test_production_with_debug_disabled_is_allowed(): void
    {
        ProductionSafety::assertSafe('production', false);

        $this->addToAssertionCount(1);
    }

    public function test_debug_mode_is_allowed_outside_production(): void
    {
        ProductionSafety::assertSafe('local', true);
        ProductionSafety::assertSafe('testing', true);

        $this->addToAssertionCount(1);
    }
}
