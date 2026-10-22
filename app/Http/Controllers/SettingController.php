<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Show the application settings form.
     */
    public function index(SettingService $settings): View
    {
        return view('settings.index', [
            'settings' => $settings,
            'values' => [
                SettingService::KEY_APP_NAME => $settings->get(SettingService::KEY_APP_NAME),
                SettingService::KEY_DEFAULT_MIN_STOCK => $settings->get(SettingService::KEY_DEFAULT_MIN_STOCK),
                SettingService::KEY_EMAIL_ALERTS_ENABLED => $settings->emailAlertsEnabled() ? '1' : '0',
                SettingService::KEY_EMAIL_ALERTS_RECIPIENT => $settings->emailAlertsRecipient(),
                SettingService::KEY_EMAIL_ALERTS_MIN_STOCK => $settings->emailAlertsMinStock(),
            ],
            'logoUrl' => $settings->appLogoUrl(),
            'locale' => auth()->user()?->locale ?? session('locale', config('app.locale')),
        ]);
    }

    /**
     * Persist the settings, replacing the logo when a new file is supplied.
     */
    public function update(UpdateSettingRequest $request, SettingService $settings): RedirectResponse
    {
        $values = [
            SettingService::KEY_APP_NAME => $request->validated('app_name'),
            SettingService::KEY_DEFAULT_MIN_STOCK => (string) $request->validated('default_min_stock'),
            // The checkbox is absent from the payload when it is unticked, so
            // the flag is read from whether the field was submitted at all.
            SettingService::KEY_EMAIL_ALERTS_ENABLED => $request->boolean('email_alerts_enabled') ? '1' : '0',
            SettingService::KEY_EMAIL_ALERTS_RECIPIENT => $request->validated('email_alerts_recipient'),
            SettingService::KEY_EMAIL_ALERTS_MIN_STOCK => (string) ($request->validated('email_alerts_min_stock') ?? 0),
        ];

        if ($request->boolean('remove_logo')) {
            $settings->deleteLogo();
        } elseif ($request->hasFile('app_logo')) {
            $settings->deleteLogo();
            $settings->set(
                SettingService::KEY_APP_LOGO,
                $settings->storeLogo($request->file('app_logo'))
            );
        }

        $settings->updateMany($values);

        $locale = $request->validated('locale')
            ?? $request->user()?->locale
            ?? session('locale', config('app.locale'));

        $request->user()->forceFill(['locale' => $locale])->save();
        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->route('settings.index')
            ->with('success', __('Settings updated successfully.'));
    }
}
