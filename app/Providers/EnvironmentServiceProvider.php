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
        if (! file_exists(storage_path('app/installed'))) {
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
            $appUrl = $env->get('app_url');
            if ($appUrl !== '') {
                $overrides['app.url'] = $appUrl;
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

            // Frontend URL / CORS
            $frontendUrl = $env->get('frontend_url');
            if ($frontendUrl !== '') {
                $overrides['cors.allowed_origins'] = [$frontendUrl];
            }

            // Filesystem
            $filesystemDisk = $env->get('filesystem_disk');
            if ($filesystemDisk !== '') {
                $overrides['filesystems.default'] = $filesystemDisk;
            }

            if (! empty($overrides)) {
                config($overrides);
            }
        } catch (\Throwable) {
            // Silently fail — DB may not be available during first install.
        }
    }
}
