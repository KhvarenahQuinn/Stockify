<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Requests\Auth\UpdateLoginLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

/**
 * Switch the interface language before a user has authenticated.
 *
 * The choice lives in the server-side session for the login screen. If the
 * visitor then signs in, LoginController copies this explicit choice to the
 * account's saved locale.
 */
class LoginLocaleController extends Controller
{
    public function update(UpdateLoginLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        $request->session()->put('locale', $locale);
        $request->session()->put(SetLocaleMiddleware::GUEST_LOCALE_KEY, $locale);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return redirect()->route('login');
    }
}
