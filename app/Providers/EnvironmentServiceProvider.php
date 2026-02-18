<?php

namespace App\Providers;

use App\Services\EnvironmentService;
use Illuminate\Support\ServiceProvider;

class EnvironmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! env('APP_INSTALLED', false) && ! file_exists(storage_path('app/installed'))) {
            return;
        }

        try {
            $env = app(EnvironmentService::class);
            $settings = $env->all();

            if (empty($settings)) {
                return;
            }

            $overrides = [];

            // Application
            $appName = $env->get('app_name');
            if ($appName !== '') {
                $overrides['app.name'] = $appName;
            }

            $appUrl = $env->get('app_url');
            if ($appUrl !== '') {
                $overrides['app.url'] = $appUrl;
            }

            $appTimezone = $env->get('app_timezone');
            if ($appTimezone !== '') {
                $overrides['app.timezone'] = $appTimezone;
            }

            $appLocale = $env->get('app_locale');
            if ($appLocale !== '') {
                $overrides['app.locale'] = $appLocale;
            }

            $appFallbackLocale = $env->get('app_fallback_locale');
            if ($appFallbackLocale !== '') {
                $overrides['app.fallback_locale'] = $appFallbackLocale;
            }

            $bcryptRounds = $env->get('bcrypt_rounds');
            if ($bcryptRounds !== '') {
                $overrides['hashing.bcrypt.rounds'] = (int) $bcryptRounds;
            }

            // Mail
            $mailMailer = $env->get('mail_mailer');
            if ($mailMailer !== '') {
                $overrides['mail.default'] = $mailMailer;
            }

            $mailHost = $env->get('mail_host');
            if ($mailHost !== '') {
                $overrides['mail.mailers.smtp.host'] = $mailHost;
            }

            $mailPort = $env->get('mail_port');
            if ($mailPort !== '') {
                $overrides['mail.mailers.smtp.port'] = (int) $mailPort;
            }

            $mailUsername = $env->get('mail_username');
            if ($mailUsername !== '') {
                $overrides['mail.mailers.smtp.username'] = $mailUsername;
            }

            $mailPassword = $env->get('mail_password');
            if ($mailPassword !== '') {
                $overrides['mail.mailers.smtp.password'] = $mailPassword;
            }

            $mailFromAddress = $env->get('mail_from_address');
            if ($mailFromAddress !== '') {
                $overrides['mail.from.address'] = $mailFromAddress;
            }

            $mailFromName = $env->get('mail_from_name');
            if ($mailFromName !== '') {
                $overrides['mail.from.name'] = $mailFromName;
            }

            $mailScheme = $env->get('mail_scheme');
            if ($mailScheme !== '') {
                $overrides['mail.mailers.smtp.scheme'] = $mailScheme;
            }

            // Logging
            $logChannel = $env->get('log_channel');
            if ($logChannel !== '') {
                $overrides['logging.default'] = $logChannel;
            }

            $logLevel = $env->get('log_level');
            if ($logLevel !== '') {
                $overrides['logging.channels.single.level'] = $logLevel;
                $overrides['logging.channels.daily.level'] = $logLevel;
            }

            // Session
            $sessionDriver = $env->get('session_driver');
            if ($sessionDriver !== '') {
                $overrides['session.driver'] = $sessionDriver;
            }

            $sessionLifetime = $env->get('session_lifetime');
            if ($sessionLifetime !== '') {
                $overrides['session.lifetime'] = (int) $sessionLifetime;
            }

            $sessionEncrypt = $env->get('session_encrypt');
            if ($sessionEncrypt !== '') {
                $overrides['session.encrypt'] = $sessionEncrypt === 'true';
            }

            $sessionSecureCookie = $env->get('session_secure_cookie');
            if ($sessionSecureCookie !== '') {
                $overrides['session.secure'] = $sessionSecureCookie === 'true';
            }

            $sessionDomain = $env->get('session_domain');
            if ($sessionDomain !== '') {
                $overrides['session.domain'] = $sessionDomain;
            }

            $sessionSameSite = $env->get('session_same_site');
            if ($sessionSameSite !== '') {
                $overrides['session.same_site'] = $sessionSameSite;
            }

            $sessionHttpOnly = $env->get('session_http_only');
            if ($sessionHttpOnly !== '') {
                $overrides['session.http_only'] = $sessionHttpOnly === 'true';
            }

            // Frontend URL / CORS
            $frontendUrl = $env->get('frontend_url');
            if ($frontendUrl !== '') {
                $overrides['cors.allowed_origins'] = [$frontendUrl];
            }

            // Cache
            $cacheStore = $env->get('cache_store');
            if ($cacheStore !== '') {
                $overrides['cache.default'] = $cacheStore;
            }

            $cachePrefix = $env->get('cache_prefix');
            if ($cachePrefix !== '') {
                $overrides['cache.prefix'] = $cachePrefix;
            }

            // Queue
            $queueConnection = $env->get('queue_connection');
            if ($queueConnection !== '') {
                $overrides['queue.default'] = $queueConnection;
            }

            // Redis
            $redisHost = $env->get('redis_host');
            if ($redisHost !== '') {
                $overrides['database.redis.default.host'] = $redisHost;
                $overrides['database.redis.cache.host'] = $redisHost;
            }

            $redisPassword = $env->get('redis_password');
            if ($redisPassword !== '') {
                $overrides['database.redis.default.password'] = $redisPassword;
                $overrides['database.redis.cache.password'] = $redisPassword;
            }

            $redisPort = $env->get('redis_port');
            if ($redisPort !== '') {
                $overrides['database.redis.default.port'] = (int) $redisPort;
                $overrides['database.redis.cache.port'] = (int) $redisPort;
            }

            // Filesystem
            $filesystemDisk = $env->get('filesystem_disk');
            if ($filesystemDisk !== '') {
                $overrides['filesystems.default'] = $filesystemDisk;
            }

            // AWS / S3
            $awsKey = $env->get('aws_access_key_id');
            if ($awsKey !== '') {
                $overrides['filesystems.disks.s3.key'] = $awsKey;
            }

            $awsSecret = $env->get('aws_secret_access_key');
            if ($awsSecret !== '') {
                $overrides['filesystems.disks.s3.secret'] = $awsSecret;
            }

            $awsRegion = $env->get('aws_default_region');
            if ($awsRegion !== '') {
                $overrides['filesystems.disks.s3.region'] = $awsRegion;
            }

            $awsBucket = $env->get('aws_bucket');
            if ($awsBucket !== '') {
                $overrides['filesystems.disks.s3.bucket'] = $awsBucket;
            }

            if (! empty($overrides)) {
                config($overrides);
            }
        } catch (\Throwable) {
            // Silently fail — DB may not be available during first install.
        }
    }
}
