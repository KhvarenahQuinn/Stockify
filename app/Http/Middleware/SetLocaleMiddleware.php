<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the interface language for the current request.
 *
 * Precedence is deliberately simple: the signed-in user's saved preference
 * wins, then the current session, then the application default. The session
 * fallback lets the language switch take effect immediately on the redirect
 * that follows the settings update, before the user row is re-read.
 */
class SetLocaleMiddleware
{
    /**
     * Locales this application ships translations for.
     *
     * @var array<int, string>
     */
    public const SUPPORTED = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?: $request->session()->get('locale')
            ?: config('app.locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
