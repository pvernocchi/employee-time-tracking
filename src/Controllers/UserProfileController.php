<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\View;

class UserProfileController
{
    public function index(): void
    {
        Auth::requireLogin();

        $db = Database::getInstance();
        $user = Auth::user();
        $prefs = $db->fetchOne(
            'SELECT * FROM user_preferences WHERE user_id = ?',
            [(int) $user['id']]
        );

        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        View::render('profile.index', [
            'user' => $user,
            'prefs' => $prefs ?: ['timezone' => 'Europe/Madrid', 'locale' => I18n::getLocale(), 'theme' => 'light'],
            'timezones' => $timezones,
            'supportedLocales' => I18n::getSupportedLocales(),
        ]);
    }

    public function savePreferences(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $db = Database::getInstance();

        $timezone = trim($_POST['timezone'] ?? 'Europe/Madrid');
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), true)) {
            $timezone = 'Europe/Madrid';
        }

        $locale = trim($_POST['locale'] ?? 'es');
        if (!in_array($locale, I18n::getSupportedLocales(), true)) {
            $locale = 'es';
        }

        $theme = trim($_POST['theme'] ?? 'light');
        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }

        $db->query(
            'INSERT INTO user_preferences (user_id, timezone, locale, theme) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE timezone = VALUES(timezone), locale = VALUES(locale), theme = VALUES(theme)',
            [$userId, $timezone, $locale, $theme]
        );

        $_SESSION['user_timezone'] = $timezone;
        $_SESSION['lang'] = $locale;
        $_SESSION['user_theme'] = $theme;

        $_SESSION['flash_success'] = I18n::translate('profile.preferences_saved');
        header('Location: /profile');
        exit;
    }

    public function changePassword(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $db = Database::getInstance();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = $db->fetchOne('SELECT password FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['flash_error'] = I18n::translate('profile.current_password_incorrect');
            header('Location: /profile');
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['flash_error'] = I18n::translate('profile.password_min_length');
            header('Location: /profile');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = I18n::translate('profile.passwords_do_not_match');
            header('Location: /profile');
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query('UPDATE users SET password = ? WHERE id = ?', [$hash, $userId]);

        $_SESSION['flash_success'] = I18n::translate('profile.password_changed');
        header('Location: /profile');
        exit;
    }
}
