<?php

namespace App\Core;

/**
 * Helper class to read / write security settings from the database.
 */
class SecuritySettings
{
    private static ?array $cache = null;

    /** Return all security settings as an associative array. */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::load();
        }
        return self::$cache;
    }

    /** Get a single setting value with an optional default. */
    public static function get(string $key, string $default = ''): string
    {
        return self::all()[$key] ?? $default;
    }

    /** Save a batch of settings (key => value). */
    public static function save(array $settings): void
    {
        $db = Database::getInstance();
        foreach ($settings as $key => $value) {
            $db->query(
                'INSERT INTO security_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [(string) $key, (string) $value]
            );
        }
        self::$cache = null; // invalidate cache
    }

    // -------------------------------------------------------------------------
    // Convenience accessors
    // -------------------------------------------------------------------------

    /** 'optional' or 'mandatory' */
    public static function mfaPolicy(): string
    {
        return self::get('mfa_policy', 'optional');
    }

    /** 'none', 'cloudflare', or 'recaptcha' */
    public static function captchaProvider(): string
    {
        return self::get('captcha_provider', 'none');
    }

    public static function captchaSiteKey(): string
    {
        return self::get('captcha_site_key', '');
    }

    public static function captchaSecretKey(): string
    {
        return self::get('captcha_secret_key', '');
    }

    /** 'v2' or 'v3' (for Google reCAPTCHA) */
    public static function recaptchaVersion(): string
    {
        return self::get('captcha_recaptcha_version', 'v2');
    }

    // -------------------------------------------------------------------------

    private static function load(): array
    {
        try {
            $db   = Database::getInstance();
            $rows = $db->fetchAll('SELECT setting_key, setting_value FROM security_settings');
            $out  = [];
            foreach ($rows as $row) {
                $out[$row['setting_key']] = $row['setting_value'];
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
