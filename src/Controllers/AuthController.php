<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CaptchaService;
use App\Core\Database;
use App\Core\I18n;
use App\Core\SecuritySettings;
use App\Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        View::render('auth.login', [
            'captchaProvider' => SecuritySettings::captchaProvider(),
            'captchaSiteKey'  => SecuritySettings::captchaSiteKey(),
            'recaptchaVersion' => SecuritySettings::recaptchaVersion(),
        ], 'auth');
    }

    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /login');
            exit;
        }

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = I18n::translate('flash.enter_email_password');
            header('Location: /login');
            exit;
        }

        // CAPTCHA verification
        $captchaToken = $_POST['cf-turnstile-response'] ?? $_POST['g-recaptcha-response'] ?? null;
        if (!CaptchaService::verify($captchaToken)) {
            $_SESSION['flash_error'] = I18n::translate('flash.captcha_failed');
            header('Location: /login');
            exit;
        }

        if (!Auth::attempt($email, $password)) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_email_or_password');
            header('Location: /login');
            exit;
        }

        // Credentials are valid – check MFA requirements
        $userId = Auth::id();
        $db     = Database::getInstance();

        $mfaMethods = $db->fetchAll(
            'SELECT id FROM user_mfa WHERE user_id = ? AND is_active = 1',
            [$userId]
        );

        $hasMfa = !empty($mfaMethods);

        // Check global policy and per-user override
        $policy      = SecuritySettings::mfaPolicy();
        $userRow     = $db->fetchOne('SELECT mfa_required FROM users WHERE id = ?', [$userId]);
        $userForced  = (bool) ($userRow['mfa_required'] ?? false);
        $mfaRequired = ($policy === 'mandatory') || $userForced;

        if ($hasMfa) {
            // User has MFA enrolled → require verification
            Auth::logout(); // clear full session set by attempt()
            $_SESSION['mfa_pending_user_id'] = $userId;
            header('Location: /mfa/verify');
            exit;
        }

        if ($mfaRequired && !$hasMfa) {
            // MFA mandatory but not yet enrolled → force setup (let user choose method)
            $_SESSION['mfa_setup_required'] = true;
            header('Location: /mfa/setup');
            exit;
        }

        // No MFA required / optional with none enrolled → log in
        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
