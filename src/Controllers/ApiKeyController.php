<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\View;

class ApiKeyController
{
    public function index(): void
    {
        Auth::requireLogin();

        $userId = Auth::id();
        $db = Database::getInstance();

        $apiKeys = $db->fetchAll(
            'SELECT id, name, is_active, expires_at, last_used_at, created_at
             FROM api_keys
             WHERE user_id = ?
             ORDER BY created_at DESC',
            [$userId]
        );

        $newApiKey = $_SESSION['new_api_key_raw'] ?? null;
        unset($_SESSION['new_api_key_raw']);

        View::render('profile.api_keys', [
            'apiKeys' => $apiKeys,
            'newApiKey' => $newApiKey,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile/api-keys');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || strlen($name) > 100) {
            $_SESSION['flash_error'] = I18n::translate('api_keys.errors.invalid_name');
            header('Location: /profile/api-keys');
            exit;
        }

        $expiresAt = null;
        $expiresAtInput = trim((string) ($_POST['expires_at'] ?? ''));
        if ($expiresAtInput !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $expiresAtInput);
            if ($dt === false || $dt->format('Y-m-d\TH:i') !== $expiresAtInput) {
                $_SESSION['flash_error'] = I18n::translate('api_keys.errors.invalid_expiration');
                header('Location: /profile/api-keys');
                exit;
            }

            if ($dt <= new \DateTime()) {
                $_SESSION['flash_error'] = I18n::translate('api_keys.errors.expiration_must_be_future');
                header('Location: /profile/api-keys');
                exit;
            }

            $expiresAt = $dt->format('Y-m-d H:i:s');
        }

        $rawKey = bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $rawKey);

        $db = Database::getInstance();
        $db->insert('api_keys', [
            'user_id' => Auth::id(),
            'name' => $name,
            'key_hash' => $keyHash,
            'is_active' => 1,
            'expires_at' => $expiresAt,
        ]);

        $_SESSION['new_api_key_raw'] = $rawKey;
        $_SESSION['flash_success'] = I18n::translate('api_keys.created');
        header('Location: /profile/api-keys');
        exit;
    }

    public function revoke(string $id): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile/api-keys');
            exit;
        }

        $apiKeyId = (int) $id;
        if ($apiKeyId <= 0) {
            $_SESSION['flash_error'] = I18n::translate('api_keys.errors.invalid_key');
            header('Location: /profile/api-keys');
            exit;
        }

        $db = Database::getInstance();
        $updated = $db->update(
            'api_keys',
            ['is_active' => 0],
            'id = ? AND user_id = ?',
            [$apiKeyId, Auth::id()]
        );

        if ($updated === 0) {
            $_SESSION['flash_error'] = I18n::translate('api_keys.errors.not_found');
            header('Location: /profile/api-keys');
            exit;
        }

        $_SESSION['flash_success'] = I18n::translate('api_keys.revoked');
        header('Location: /profile/api-keys');
        exit;
    }
}
