<?php

namespace App\Support;

use RuntimeException;

final class ProductionSafety
{
    /**
     * Refuse to boot a production instance with verbose error output enabled.
     *
     * APP_DEBUG=true exposes stack traces, environment values and SQL to any
     * visitor who can trigger an exception. This guard turns that dangerous
     * combination into a startup failure rather than a silent data leak.
     */
    public static function assertSafe(string $environment, bool $debug): void
    {
        if ($environment === 'production' && $debug) {
            throw new RuntimeException(
                'Refusing to start: APP_DEBUG=true while APP_ENV=production. Set APP_DEBUG=false before deploying.'
            );
        }
    }
}
