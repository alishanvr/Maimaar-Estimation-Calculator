<?php

namespace App\Services;

use App\Models\DesignConfiguration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class EnvironmentService
{
    private const CACHE_KEY = 'environment_settings';

    private const CACHE_TTL = 86400;

    private const CATEGORY = 'environment_settings';

    /**
     * Keys that store encrypted values.
     *
     * @var array<int, string>
     */
    private const ENCRYPTED_KEYS = [
        'mail_password',
    ];

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
        $value = $this->all()[$key] ?? $default;

        if (in_array($key, self::ENCRYPTED_KEYS) && $value !== '' && $value !== $default) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $default;
            }
        }

        return $value;
    }

    public function appUrl(): string
    {
        return $this->get('app_url', config('app.url') ?? 'http://localhost');
    }

    public function mailMailer(): string
    {
        return $this->get('mail_mailer', config('mail.default') ?? 'log');
    }

    public function mailHost(): string
    {
        return $this->get('mail_host', config('mail.mailers.smtp.host') ?? '127.0.0.1');
    }

    public function mailPort(): int
    {
        return (int) $this->get('mail_port', (string) (config('mail.mailers.smtp.port') ?? 2525));
    }

    public function mailUsername(): string
    {
        return $this->get('mail_username', (string) config('mail.mailers.smtp.username'));
    }

    public function mailPassword(): string
    {
        return $this->get('mail_password', (string) config('mail.mailers.smtp.password'));
    }

    public function mailFromAddress(): string
    {
        return $this->get('mail_from_address', config('mail.from.address') ?? 'hello@example.com');
    }

    public function mailFromName(): string
    {
        return $this->get('mail_from_name', config('mail.from.name') ?? 'Maimaar');
    }

    public function mailScheme(): ?string
    {
        $scheme = $this->get('mail_scheme', '');

        return $scheme !== '' ? $scheme : null;
    }

    public function logChannel(): string
    {
        return $this->get('log_channel', config('logging.default') ?? 'stack');
    }

    public function logLevel(): string
    {
        return $this->get('log_level', config('logging.channels.single.level') ?? 'debug');
    }

    public function sessionLifetime(): int
    {
        return (int) $this->get('session_lifetime', (string) (config('session.lifetime') ?? 120));
    }

    public function sessionEncrypt(): bool
    {
        return $this->get('session_encrypt', 'false') === 'true';
    }

    public function sessionSecureCookie(): bool
    {
        return $this->get('session_secure_cookie', 'false') === 'true';
    }

    public function frontendUrl(): string
    {
        return $this->get('frontend_url', config('cors.frontend_url') ?? 'http://localhost:3000');
    }

    public function filesystemDisk(): string
    {
        return $this->get('filesystem_disk', config('filesystems.default') ?? 'local');
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
