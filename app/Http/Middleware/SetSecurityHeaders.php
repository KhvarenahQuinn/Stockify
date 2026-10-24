<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach the browser-facing security headers to every web response.
 *
 * The policy is intentionally strict: Vite compiles the CSS and JS bundles to
 * the application origin, so nothing needs to be loaded from a third-party
 * CDN. Inline script and style execution stay allowed because the shell uses
 * a pre-paint theme script and Flowbite drives its widgets through inline
 * style attributes.
 */
class SetSecurityHeaders
{
    /**
     * Content Security Policy applied to the application shell.
     */
    public const CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; frame-ancestors 'none'; object-src 'none'";

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // The framework version is useful to an attacker and of no use to a
        // browser, so it does not need to travel on every response.
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Choose the policy for this environment.
     *
     * Vite's development server serves the bundles from another origin, so
     * local HMR needs those origins in the policy. Production builds resolve
     * to the application origin and keep the strict shipping policy.
     */
    private function contentSecurityPolicy(): string
    {
        $policy = self::CONTENT_SECURITY_POLICY;

        if (! app()->environment('local')) {
            return $policy;
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return $policy;
        }

        $devServer = rtrim(trim((string) file_get_contents($hotFile)), '/');

        if ($devServer === '') {
            return $policy;
        }

        $socketServer = preg_replace('/^http/', 'ws', $devServer);

        return str_replace(
            [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
            ],
            [
                "default-src 'self' {$devServer} {$socketServer}",
                "script-src 'self' 'unsafe-inline' {$devServer} {$socketServer}",
                "style-src 'self' 'unsafe-inline' {$devServer}",
            ],
            $policy,
        );
    }
}
