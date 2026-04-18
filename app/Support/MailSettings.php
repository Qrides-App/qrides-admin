<?php

namespace App\Support;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Config;

class MailSettings
{
    public const META_KEYS = [
        'emailwizard_enabled',
        'emailwizard_driver',
        'emailwizard_mailer_name',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'general_name',
    ];

    public static function values(): array
    {
        return GeneralSetting::whereIn('meta_key', self::META_KEYS)
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }

    public static function normalize(?array $settings = null): array
    {
        $settings ??= self::values();

        $enabledRaw = $settings['emailwizard_enabled'] ?? $settings['enabled'] ?? 1;
        $enabled = ! in_array(strtolower((string) $enabledRaw), ['0', 'false', 'off', 'inactive'], true);

        return [
            'enabled' => $enabled,
            'driver' => trim((string) ($settings['emailwizard_driver'] ?? $settings['driver'] ?? 'smtp')) ?: 'smtp',
            'mailer_name' => trim((string) ($settings['emailwizard_mailer_name'] ?? $settings['mailer_name'] ?? $settings['general_name'] ?? config('app.name'))),
            'host' => trim((string) ($settings['host'] ?? config('mail.mailers.smtp.host', ''))),
            'port' => (string) ($settings['port'] ?? config('mail.mailers.smtp.port', '587')),
            'username' => trim((string) ($settings['username'] ?? config('mail.mailers.smtp.username', ''))),
            'password' => (string) ($settings['password'] ?? config('mail.mailers.smtp.password', '')),
            'encryption' => trim((string) ($settings['encryption'] ?? config('mail.mailers.smtp.encryption', 'tls'))),
            'from_email' => trim((string) ($settings['from_email'] ?? config('mail.from.address', ''))),
        ];
    }

    public static function apply(?array $settings = null): array
    {
        $config = self::normalize($settings);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => $config['driver'],
            'host' => $config['host'],
            'port' => ((int) $config['port']) ?: null,
            'encryption' => $config['encryption'] ?: null,
            'username' => $config['username'],
            'password' => $config['password'],
            'timeout' => null,
            'auth_mode' => null,
        ]);

        Config::set('mail.from', [
            'address' => $config['from_email'],
            'name' => $config['mailer_name'] ?: config('app.name'),
        ]);

        return $config;
    }

    public static function isConfigured(?array $settings = null): bool
    {
        $config = self::normalize($settings);

        return $config['enabled']
            && $config['host'] !== ''
            && $config['username'] !== ''
            && $config['password'] !== ''
            && $config['from_email'] !== '';
    }
}
