<?php

namespace App\Core;

class View
{
    private static string $viewsPath = '';

    public static function setPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $t = static fn (string $key, array $replacements = []): string => I18n::translate($key, $replacements);
        $currentLocale = I18n::getLocale();
        $supportedLocales = I18n::getSupportedLocales();
        $localeMeta = static fn (string $locale): array => I18n::getLocaleMeta($locale);

        $viewFile = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = I18n::translateContent((string) ob_get_clean());

        if ($layout) {
            $layoutFile = self::$viewsPath . '/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layout}");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function partial(string $view, array $data = []): void
    {
        extract($data);
        $t = static fn (string $key, array $replacements = []): string => I18n::translate($key, $replacements);
        $currentLocale = I18n::getLocale();
        $supportedLocales = I18n::getSupportedLocales();
        $localeMeta = static fn (string $locale): array => I18n::getLocaleMeta($locale);
        $viewFile = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }
}
