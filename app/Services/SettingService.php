<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingService
{
    public const CACHE_KEY = 'settings.all';

    public const CACHE_TTL_SECONDS = 3600;

    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'settings';

    public const LOGO_MAX_KILOBYTES = 1024;

    public const KEY_APP_NAME = 'app_name';

    public const KEY_APP_LOGO = 'app_logo';

    public const KEY_DEFAULT_MIN_STOCK = 'default_min_stock';

    public const KEY_EMAIL_ALERTS_ENABLED = 'email_alerts_enabled';

    public const KEY_EMAIL_ALERTS_RECIPIENT = 'email_alerts_recipient';

    public const KEY_EMAIL_ALERTS_MIN_STOCK = 'email_alerts_min_stock';

    /**
     * Defaults applied when a key has never been stored, so the app renders
     * sensibly on a database that was never seeded.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        self::KEY_APP_NAME => 'Stockify',
        self::KEY_DEFAULT_MIN_STOCK => '10',
        // Off until somebody supplies a recipient, so a fresh install does not
        // try to mail an address that does not exist.
        self::KEY_EMAIL_ALERTS_ENABLED => '0',
        self::KEY_EMAIL_ALERTS_RECIPIENT => '',
        self::KEY_EMAIL_ALERTS_MIN_STOCK => '0',
    ];

    /**
     * Every setting as a flat key => value map, cached.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default ?? (self::DEFAULTS[$key] ?? null);
        }

        return $value;
    }

    /**
     * Store a single key, replacing any existing value.
     */
    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        $this->forget();
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function updateMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($values !== []) {
            $this->forget();
        }
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function appName(): string
    {
        return $this->get(self::KEY_APP_NAME, self::DEFAULTS[self::KEY_APP_NAME]) ?? 'Stockify';
    }

    public function appLogo(): ?string
    {
        return $this->get(self::KEY_APP_LOGO);
    }

    /**
     * Public URL for the stored logo, or null when none is configured.
     *
     * Accepts a stored disk path, an absolute URL, or a root-relative asset
     * path so seeded and uploaded values both work.
     */
    public function appLogoUrl(): ?string
    {
        $logo = $this->appLogo();

        if ($logo === null || trim($logo) === '') {
            return null;
        }

        if (str_starts_with($logo, 'http://')
            || str_starts_with($logo, 'https://')
            || str_starts_with($logo, '/')
        ) {
            return $logo;
        }

        return Storage::disk(self::LOGO_DISK)->url($logo);
    }

    /**
     * Reorder threshold used when pre-filling new product forms.
     */
    public function defaultMinStock(): int
    {
        $value = $this->get(self::KEY_DEFAULT_MIN_STOCK, self::DEFAULTS[self::KEY_DEFAULT_MIN_STOCK]);

        return is_numeric($value) ? (int) $value : (int) self::DEFAULTS[self::KEY_DEFAULT_MIN_STOCK];
    }

    /**
     * Whether low-stock email alerts are switched on.
     */
    public function emailAlertsEnabled(): bool
    {
        return $this->get(self::KEY_EMAIL_ALERTS_ENABLED, '0') === '1';
    }

    /**
     * Where the alerts are sent, or null when no address is set.
     */
    public function emailAlertsRecipient(): ?string
    {
        $value = $this->get(self::KEY_EMAIL_ALERTS_RECIPIENT, '');

        return $value === '' ? null : $value;
    }

    /**
     * The stock level at or below which an alert is raised.
     */
    public function emailAlertsMinStock(): int
    {
        return (int) $this->get(self::KEY_EMAIL_ALERTS_MIN_STOCK, '0');
    }

    /**
     * Validate and store an uploaded logo on the public disk.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeLogo(UploadedFile $logo): string
    {
        Validator::make(
            ['app_logo' => $logo],
            [
                'app_logo' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,webp',
                    'max:'.self::LOGO_MAX_KILOBYTES,
                ],
            ],
            [
                'app_logo.required' => 'Please choose a logo file to upload.',
                'app_logo.image' => 'The logo must be a valid image file.',
                'app_logo.mimes' => 'The logo must be a JPEG, PNG or WebP file.',
                'app_logo.max' => 'The logo may not be greater than 1 MB.',
            ]
        )->validate();

        $path = $logo->store(self::LOGO_DIRECTORY, self::LOGO_DISK);

        if ($path === false) {
            throw new \RuntimeException(__('Unable to store the application logo.'));
        }

        return $path;
    }

    public function deleteLogo(): void
    {
        $logo = $this->appLogo();

        if ($logo === null || $logo === '' || str_starts_with($logo, 'http') || str_starts_with($logo, '/')) {
            return;
        }

        Storage::disk(self::LOGO_DISK)->delete($logo);

        $this->set(self::KEY_APP_LOGO, null);
    }
}