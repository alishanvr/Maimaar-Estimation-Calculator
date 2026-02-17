<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class EnvWriter
{
    /**
     * Set a single environment variable in the .env file.
     */
    public function set(string $key, string $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * Set multiple environment variables in the .env file.
     *
     * @param  array<string, string>  $keyValues
     */
    public function setMany(array $keyValues): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $content = file_get_contents($envPath);

        foreach ($keyValues as $key => $value) {
            $formattedValue = $this->formatValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formattedValue}", $content);
            } else {
                $content = rtrim($content, "\n")."\n{$key}={$formattedValue}\n";
            }
        }

        file_put_contents($envPath, $content, LOCK_EX);

        $this->clearConfigCache();
    }

    /**
     * Format a value for safe .env file storage.
     */
    private function formatValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#"\'\\\\]/', $value) || str_contains($value, '${')) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return "\"{$escaped}\"";
        }

        return $value;
    }

    /**
     * Clear the config cache so new .env values take effect.
     */
    private function clearConfigCache(): void
    {
        if (app()->configurationIsCached()) {
            Artisan::call('config:clear');
        }
    }
}
