<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\SecuritySettings;
use App\Core\TotpService;
use App\Core\View;
use App\Core\WebAuthnService;

class MfaController
{
    // -------------------------------------------------------------------------
    // Verification during login (shown after successful password auth)
    // -------------------------------------------------------------------------

    public function showVerify(): void
    {
        if (!isset($_SESSION['mfa_pending_user_id'])) {
            header('Location: /login');
            exit;
        }

        $db      = Database::getInstance();
        $methods = $db->fetchAll(
            'SELECT id, type, name FROM user_mfa WHERE user_id = ? AND is_active = 1',
            [(int) $_SESSION['mfa_pending_user_id']]
        );

        if (empty($methods)) {
            // No MFA methods enrolled – complete login directly
            $this->completePendingLogin();
            return;
        }

        View::render('mfa.verify', ['methods' => $methods], 'auth');
    }

    public function verify(): void
    {
        if (!isset($_SESSION['mfa_pending_user_id'])) {
            header('Location: /login');
            exit;
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /mfa/verify');
            exit;
        }

        $db     = Database::getInstance();
        $userId = (int) $_SESSION['mfa_pending_user_id'];
        $method = $_POST['mfa_method'] ?? 'totp';

        if ($method === 'totp') {
            $code   = trim($_POST['totp_code'] ?? '');
            $totpRow = $db->fetchOne(
                'SELECT id, credential_data FROM user_mfa WHERE user_id = ? AND type = "totp" AND is_active = 1',
                [$userId]
            );

            if (!$totpRow) {
                $_SESSION['flash_error'] = I18n::translate('mfa.no_totp_enrolled');
                header('Location: /mfa/verify');
                exit;
            }

            $data   = json_decode($totpRow['credential_data'], true);
            $secret = $data['secret'] ?? '';

            if (!TotpService::verify($secret, $code)) {
                $_SESSION['flash_error'] = I18n::translate('mfa.invalid_totp_code');
                header('Location: /mfa/verify');
                exit;
            }

            $db->query(
                'UPDATE user_mfa SET last_used_at = NOW() WHERE id = ?',
                [(int) $totpRow['id']]
            );
        } else {
            $_SESSION['flash_error'] = I18n::translate('mfa.unsupported_method');
            header('Location: /mfa/verify');
            exit;
        }

        $this->completePendingLogin();
    }

    // -------------------------------------------------------------------------
    // WebAuthn verification (AJAX endpoints)
    // -------------------------------------------------------------------------

    public function webAuthnAuthChallenge(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mfa_pending_user_id'])) {
            echo json_encode(['error' => 'No pending login']);
            return;
        }

        $db      = Database::getInstance();
        $userId  = (int) $_SESSION['mfa_pending_user_id'];
        $creds   = $db->fetchAll(
            'SELECT id, credential_data FROM user_mfa WHERE user_id = ? AND type = "webauthn" AND is_active = 1',
            [$userId]
        );

        if (empty($creds)) {
            echo json_encode(['error' => 'No WebAuthn credentials enrolled']);
            return;
        }

        try {
            $service   = new WebAuthnService();
            $challenge = $service->createAuthChallenge($creds);
            echo json_encode($challenge);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function webAuthnAuthVerify(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mfa_pending_user_id'])) {
            echo json_encode(['success' => false, 'error' => 'No pending login']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $db     = Database::getInstance();
        $userId = (int) $_SESSION['mfa_pending_user_id'];
        $creds  = $db->fetchAll(
            'SELECT id, credential_data FROM user_mfa WHERE user_id = ? AND type = "webauthn" AND is_active = 1',
            [$userId]
        );

        try {
            $service     = new WebAuthnService();
            $matchedCred = $service->verifyAuthentication($input, $creds);

            // Update credential_data (already contains updated sign_count from service)
            $db->query(
                'UPDATE user_mfa SET credential_data = ?, last_used_at = NOW() WHERE id = ?',
                [$matchedCred['credential_data'], (int) $matchedCred['id']]
            );

            $this->completePendingLogin(false);
            echo json_encode(['success' => true, 'redirect' => '/dashboard']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // MFA Setup (user self-service)
    // -------------------------------------------------------------------------

    public function showSetup(): void
    {
        Auth::requireLogin();

        $db      = Database::getInstance();
        $userId  = Auth::id();
        $methods = $db->fetchAll(
            'SELECT id, type, name, created_at, last_used_at FROM user_mfa WHERE user_id = ? ORDER BY created_at',
            [$userId]
        );

        // Check if setup is being forced (mandatory policy, no methods yet)
        $forceSetup = isset($_SESSION['mfa_setup_required']);

        View::render('mfa.setup', [
            'methods'    => $methods,
            'forceSetup' => $forceSetup,
        ]);
    }

    // -------------------------------------------------------------------------
    // TOTP enrollment
    // -------------------------------------------------------------------------

    public function showTotpEnroll(): void
    {
        $this->requireLoginOrPending();

        $secret = TotpService::generateSecret();
        $_SESSION['totp_enroll_secret'] = $secret;

        $userId  = Auth::id() ?? $_SESSION['mfa_pending_user_id'] ?? null;
        $db      = Database::getInstance();
        $user    = $db->fetchOne('SELECT email, first_name FROM users WHERE id = ?', [(int) $userId]);
        $appName = 'Employee Time Tracker';

        $otpUri = TotpService::getOtpAuthUri($secret, $user['email'] ?? '', $appName);

        View::render('mfa.totp_enroll', [
            'secret' => $secret,
            'otpUri' => $otpUri,
        ], $this->layout());
    }

    public function storeTotpEnroll(): void
    {
        $this->requireLoginOrPending();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /mfa/enroll/totp');
            exit;
        }

        $secret = $_SESSION['totp_enroll_secret'] ?? '';
        if (empty($secret)) {
            $_SESSION['flash_error'] = I18n::translate('mfa.session_expired_retry');
            header('Location: /mfa/enroll/totp');
            exit;
        }

        $code = trim($_POST['totp_code'] ?? '');
        if (!TotpService::verify($secret, $code)) {
            $_SESSION['flash_error'] = I18n::translate('mfa.invalid_totp_code');
            header('Location: /mfa/enroll/totp');
            exit;
        }

        $userId      = $this->resolveUserId();
        $deviceName  = trim($_POST['device_name'] ?? 'Authenticator App');
        if (empty($deviceName)) {
            $deviceName = 'Authenticator App';
        }

        $db = Database::getInstance();
        $db->insert('user_mfa', [
            'user_id'         => $userId,
            'type'            => 'totp',
            'name'            => $deviceName,
            'credential_data' => json_encode(['secret' => $secret]),
        ]);

        unset($_SESSION['totp_enroll_secret']);

        if (isset($_SESSION['mfa_setup_required'])) {
            unset($_SESSION['mfa_setup_required']);
            $this->completePendingLogin();
            return;
        }

        $_SESSION['flash_success'] = I18n::translate('mfa.totp_enrolled_successfully');
        header('Location: /mfa/setup');
        exit;
    }

    // -------------------------------------------------------------------------
    // WebAuthn enrollment
    // -------------------------------------------------------------------------

    public function webAuthnRegisterChallenge(): void
    {
        $this->requireLoginOrPending();
        header('Content-Type: application/json');

        $userId  = $this->resolveUserId();
        $db      = Database::getInstance();
        $user    = $db->fetchOne('SELECT email, first_name, last_name FROM users WHERE id = ?', [$userId]);

        try {
            $service   = new WebAuthnService();
            $challenge = $service->createRegistrationChallenge(
                $userId,
                $user['email'] ?? '',
                ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')
            );
            echo json_encode($challenge);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function webAuthnRegisterVerify(): void
    {
        $this->requireLoginOrPending();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $userId     = $this->resolveUserId();
        $deviceName = trim($input['device_name'] ?? 'Security Key');
        if (empty($deviceName)) {
            $deviceName = 'Security Key';
        }

        try {
            $service    = new WebAuthnService();
            $credData   = $service->verifyRegistration(
                ['clientDataJSON'    => $input['clientDataJSON'] ?? ''],
                ['attestationObject' => $input['attestationObject'] ?? '']
            );

            $db = Database::getInstance();
            $db->insert('user_mfa', [
                'user_id'         => $userId,
                'type'            => 'webauthn',
                'name'            => $deviceName,
                'credential_id'   => $credData['credential_id'],
                'credential_data' => json_encode($credData),
            ]);

            if (isset($_SESSION['mfa_setup_required'])) {
                unset($_SESSION['mfa_setup_required']);
                $this->completePendingLogin(false);
                echo json_encode(['success' => true, 'redirect' => '/dashboard']);
            } else {
                echo json_encode(['success' => true, 'redirect' => '/mfa/setup']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // Remove an enrolled MFA method (self-service)
    // -------------------------------------------------------------------------

    public function removeMfaMethod(string $mfaId): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /mfa/setup');
            exit;
        }

        $db     = Database::getInstance();
        $method = $db->fetchOne(
            'SELECT id FROM user_mfa WHERE id = ? AND user_id = ?',
            [(int) $mfaId, Auth::id()]
        );

        if (!$method) {
            $_SESSION['flash_error'] = 'MFA method not found.';
            header('Location: /mfa/setup');
            exit;
        }

        $db->query('DELETE FROM user_mfa WHERE id = ?', [(int) $mfaId]);

        $_SESSION['flash_success'] = I18n::translate('mfa.method_removed');
        header('Location: /mfa/setup');
        exit;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function completePendingLogin(bool $redirect = true): void
    {
        $userId = (int) ($_SESSION['mfa_pending_user_id'] ?? Auth::id());
        unset($_SESSION['mfa_pending_user_id']);

        if ($userId && !Auth::check()) {
            $db   = Database::getInstance();
            $user = $db->fetchOne(
                'SELECT * FROM users WHERE id = ? AND is_active = 1',
                [$userId]
            );
            if ($user) {
                unset($user['password']);
                $_SESSION['user']          = $user;
                $_SESSION['last_activity'] = time();
            }
        }

        if ($redirect) {
            header('Location: /dashboard');
            exit;
        }
    }

    /** Require either a logged-in user OR a pending MFA login. */
    private function requireLoginOrPending(): void
    {
        if (!Auth::check() && !isset($_SESSION['mfa_pending_user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    /** Return the user ID from session (logged in or pending login). */
    private function resolveUserId(): int
    {
        return Auth::id() ?? (int) ($_SESSION['mfa_pending_user_id'] ?? 0);
    }

    /** Return the layout name depending on whether user is logged in. */
    private function layout(): string
    {
        return Auth::check() ? 'main' : 'auth';
    }
}
