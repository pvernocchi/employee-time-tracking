<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\SecuritySettings;
use App\Core\View;

class SecurityController
{
    public function settings(): void
    {
        Auth::requireAdmin();

        $settings = SecuritySettings::all();

        View::render('admin.security', [
            'settings' => $settings,
        ]);
    }

    public function saveSettings(): void
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/security');
            exit;
        }

        $mfaPolicy = $_POST['mfa_policy'] ?? 'optional';
        if (!in_array($mfaPolicy, ['optional', 'mandatory'])) {
            $mfaPolicy = 'optional';
        }

        $captchaProvider = $_POST['captcha_provider'] ?? 'none';
        if (!in_array($captchaProvider, ['none', 'cloudflare', 'recaptcha'])) {
            $captchaProvider = 'none';
        }

        $recaptchaVersion = $_POST['captcha_recaptcha_version'] ?? 'v2';
        if (!in_array($recaptchaVersion, ['v2', 'v3'])) {
            $recaptchaVersion = 'v2';
        }

        SecuritySettings::save([
            'mfa_policy'                 => $mfaPolicy,
            'captcha_provider'           => $captchaProvider,
            'captcha_site_key'           => trim($_POST['captcha_site_key'] ?? ''),
            'captcha_secret_key'         => trim($_POST['captcha_secret_key'] ?? ''),
            'captcha_recaptcha_version'  => $recaptchaVersion,
        ]);

        $_SESSION['flash_success'] = 'Security settings saved.';
        header('Location: /admin/security');
        exit;
    }

    /**
     * Admin view of all enrolled MFA devices for a user.
     */
    public function userMfaIndex(string $userId): void
    {
        Auth::requireAdmin();

        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT id, first_name, last_name, email, mfa_required FROM users WHERE id = ?',
            [(int) $userId]
        );

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            header('Location: /admin/employees');
            exit;
        }

        $methods = $db->fetchAll(
            'SELECT id, type, name, is_active, created_at, last_used_at FROM user_mfa WHERE user_id = ? ORDER BY created_at',
            [(int) $userId]
        );

        View::render('admin.user_mfa', [
            'user'    => $user,
            'methods' => $methods,
        ]);
    }

    /**
     * Toggle the per-user MFA requirement override.
     */
    public function toggleUserMfaRequired(string $userId): void
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header("Location: /admin/security/users/{$userId}/mfa");
            exit;
        }

        $db  = Database::getInstance();
        $req = isset($_POST['mfa_required']) ? 1 : 0;
        $db->update('users', ['mfa_required' => $req], 'id = ?', [(int) $userId]);

        $_SESSION['flash_success'] = 'User MFA requirement updated.';
        header("Location: /admin/security/users/{$userId}/mfa");
        exit;
    }

    /**
     * Revoke an MFA method (admin action).
     */
    public function revokeMfaMethod(string $mfaId): void
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/employees');
            exit;
        }

        $db     = Database::getInstance();
        $method = $db->fetchOne('SELECT user_id FROM user_mfa WHERE id = ?', [(int) $mfaId]);

        if (!$method) {
            $_SESSION['flash_error'] = 'MFA method not found.';
            header('Location: /admin/employees');
            exit;
        }

        $db->query('DELETE FROM user_mfa WHERE id = ?', [(int) $mfaId]);

        $_SESSION['flash_success'] = 'MFA method revoked.';
        header("Location: /admin/security/users/{$method['user_id']}/mfa");
        exit;
    }
}
