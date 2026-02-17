<?php

namespace App\Services;

use App\Models\DesignConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppSettingsService
{
    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 86400;

    private const CATEGORY = 'app_settings';

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DesignConfiguration::query()
                ->byCategory(self::CATEGORY)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->all()[$key] ?? $default;
    }

    public function appName(): string
    {
        return $this->get('app_name', 'Maimaar Estimation Calculator');
    }

    public function companyName(): string
    {
        return $this->get('company_name', 'Maimaar');
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('app_logo_path', '');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->get('favicon_path', '');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }

    public function primaryColor(): string
    {
        return $this->get('primary_color', '#3B82F6');
    }

    public function enableFillTestData(): bool
    {
        return $this->get('enable_fill_test_data', 'false') === 'true';
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
