<?php

namespace App\Core;

class I18n
{
    private static string $locale = 'es';

    private static array $translations = [];

    private static array $supportedLocales = ['es', 'en'];

    public static function init(array $appConfig = []): void
    {
        $defaultLocale = self::sanitizeLocale($appConfig['default_locale'] ?? 'es');
        $configSupportedLocales = array_filter(
            array_map([self::class, 'sanitizeLocale'], (array) ($appConfig['supported_locales'] ?? self::$supportedLocales))
        );

        self::$supportedLocales = !empty($configSupportedLocales) ? array_values(array_unique($configSupportedLocales)) : self::$supportedLocales;

        $requestedLocale = self::sanitizeLocale($_GET['lang'] ?? '');
        if ($requestedLocale && in_array($requestedLocale, self::$supportedLocales, true)) {
            $_SESSION['lang'] = $requestedLocale;
        }

        $sessionLocale = self::sanitizeLocale($_SESSION['lang'] ?? '');
        self::$locale = $sessionLocale && in_array($sessionLocale, self::$supportedLocales, true)
            ? $sessionLocale
            : $defaultLocale;

        if (!in_array(self::$locale, self::$supportedLocales, true)) {
            self::$locale = self::$supportedLocales[0];
        }

        self::$translations = self::loadTranslations(self::$locale);
    }

    public static function translate(string $key, array $replacements = []): string
    {
        $translation = self::$translations[$key] ?? $key;

        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace(':' . $placeholder, (string) $value, $translation);
        }

        return $translation;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function getSupportedLocales(): array
    {
        return self::$supportedLocales;
    }

    public static function urlWithLang(string $locale): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY), $query);
        $query['lang'] = $locale;
        return $path . '?' . http_build_query($query);
    }

    private static function loadTranslations(string $locale): array
    {
        $langFile = dirname(__DIR__) . '/Lang/' . $locale . '.php';
        if (file_exists($langFile)) {
            $translations = require $langFile;
            if (is_array($translations)) {
                return $translations;
            }
        }

        return [];
    }

    private static function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-z]/', '', strtolower($locale));
    }
}
