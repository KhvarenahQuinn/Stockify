<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Per-user preferences. Unlike /settings, which changes application-wide
 * values and stays administrator-only, these belong to the signed-in account
 * and are available to every role.
 */
class PreferencesController extends Controller
{
    public function edit(Request $request): View
    {
        return view('preferences.edit', [
            'locale' => $request->user()?->locale
                ?: session('locale', config('app.locale')),
        ]);
    }

    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        $request->user()->forceFill(['locale' => $locale])->save();
        $request->session()->put('locale', $locale);
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return redirect()->route('preferences.edit')
            ->with('success', __('Language updated successfully.'));
    }
}
