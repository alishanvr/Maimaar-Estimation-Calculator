<?php

namespace App\Services\Pdf;

use App\Models\DesignConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PdfSettingsService
{
    private const CACHE_KEY = 'pdf_settings';

    private const CACHE_TTL = 86400;

    private const CATEGORY = 'pdf_settings';

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

    public function companyName(): string
    {
        return $this->get('company_name', 'Maimaar Group');
    }

    public function fontFamily(): string
    {
        return $this->get('font_family', 'dejavu-sans');
    }

    public function fontFamilyCss(): string
    {
        return match ($this->fontFamily()) {
            'dejavu-sans' => "'DejaVu Sans', Arial, sans-serif",
            'dejavu-serif' => "'DejaVu Serif', Georgia, serif",
            'dejavu-sans-mono' => "'DejaVu Sans Mono', monospace",
            'helvetica' => 'Helvetica, Arial, sans-serif',
            'times' => "Times, 'Times New Roman', serif",
            'courier' => "Courier, 'Courier New', monospace",
            default => "'DejaVu Sans', Arial, sans-serif",
        };
    }

    public function headerColor(): string
    {
        return $this->get('header_color', '#1e3a5f');
    }

    public function bodyFontSize(): string
    {
        return $this->get('body_font_size', '11');
    }

    public function bodyLineHeight(): string
    {
        return $this->get('body_line_height', '1.4');
    }

    public function showPageNumbers(): bool
    {
        return (bool) $this->get('show_page_numbers', '1');
    }

    public function paperSize(): string
    {
        return $this->get('paper_size', 'a4');
    }

    public function logoAbsolutePath(): ?string
    {
        $path = $this->get('company_logo_path', '');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        if (file_exists(public_path('images/logo.jpeg'))) {
            return public_path('images/logo.jpeg');
        }

        if (file_exists(public_path('images/logo.png'))) {
            return public_path('images/logo.png');
        }

        return null;
    }

    public function footerText(): string
    {
        return $this->get('footer_text', '');
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
