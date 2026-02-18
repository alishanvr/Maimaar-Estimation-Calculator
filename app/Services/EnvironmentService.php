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
        'redis_password',
        'aws_access_key_id',
        'aws_secret_access_key',
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

    public function appName(): string
    {
        return $this->get('app_name', config('app.name') ?? 'Laravel');
    }

    public function appUrl(): string
    {
        return $this->get('app_url', config('app.url') ?? 'http://localhost');
    }

    public function appTimezone(): string
    {
        return $this->get('app_timezone', config('app.timezone') ?? 'UTC');
    }

    public function appLocale(): string
    {
        return $this->get('app_locale', config('app.locale') ?? 'en');
    }

    public function appFallbackLocale(): string
    {
        return $this->get('app_fallback_locale', config('app.fallback_locale') ?? 'en');
    }

    public function bcryptRounds(): int
    {
        return (int) $this->get('bcrypt_rounds', (string) (config('hashing.bcrypt.rounds') ?? 12));
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

    public function sessionDriver(): string
    {
        return $this->get('session_driver', config('session.driver') ?? 'database');
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

    public function sessionDomain(): ?string
    {
        $value = $this->get('session_domain', '');

        return $value !== '' ? $value : null;
    }

    public function sessionSameSite(): string
    {
        return $this->get('session_same_site', config('session.same_site') ?? 'lax');
    }

    public function sessionHttpOnly(): bool
    {
        return $this->get('session_http_only', 'true') === 'true';
    }

    public function frontendUrl(): string
    {
        return $this->get('frontend_url', config('cors.frontend_url') ?? 'http://localhost:3000');
    }

    public function filesystemDisk(): string
    {
        return $this->get('filesystem_disk', config('filesystems.default') ?? 'local');
    }

    public function cacheStore(): string
    {
        return $this->get('cache_store', config('cache.default') ?? 'database');
    }

    public function cachePrefix(): string
    {
        return $this->get('cache_prefix', config('cache.prefix') ?? '');
    }

    public function queueConnection(): string
    {
        return $this->get('queue_connection', config('queue.default') ?? 'database');
    }

    public function redisHost(): string
    {
        return $this->get('redis_host', config('database.redis.default.host') ?? '127.0.0.1');
    }

    public function redisPassword(): string
    {
        return $this->get('redis_password', '');
    }

    public function redisPort(): int
    {
        return (int) $this->get('redis_port', (string) (config('database.redis.default.port') ?? 6379));
    }

    public function awsAccessKeyId(): string
    {
        return $this->get('aws_access_key_id', '');
    }

    public function awsSecretAccessKey(): string
    {
        return $this->get('aws_secret_access_key', '');
    }

    public function awsDefaultRegion(): string
    {
        return $this->get('aws_default_region', config('filesystems.disks.s3.region') ?? 'us-east-1');
    }

    public function awsBucket(): string
    {
        return $this->get('aws_bucket', config('filesystems.disks.s3.bucket') ?? '');
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
