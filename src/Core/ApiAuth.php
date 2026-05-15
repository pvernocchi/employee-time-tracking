<?php

namespace App\Core;

class ApiAuth
{
    private static ?array $apiUser = null;

    public static function requireApiKey(): bool
    {
        $apiKey = self::extractApiKey();
        if ($apiKey === '') {
            self::deny(401, 'API key is required.');
            return false;
        }

        $db = Database::getInstance();
        $apiKeyHash = hash('sha256', $apiKey);

        $record = $db->fetchOne(
            'SELECT ak.id AS api_key_id, ak.user_id, u.email, u.first_name, u.last_name, u.role
             FROM api_keys ak
             INNER JOIN users u ON u.id = ak.user_id
             WHERE ak.key_hash = ?
               AND ak.is_active = 1
               AND u.is_active = 1
               AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
             LIMIT 1',
            [$apiKeyHash]
        );

        if (!$record) {
            self::deny(401, 'Invalid API key.');
            return false;
        }

        self::$apiUser = [
            'id' => (int) $record['user_id'],
            'email' => (string) $record['email'],
            'first_name' => (string) $record['first_name'],
            'last_name' => (string) $record['last_name'],
            'role' => (string) $record['role'],
        ];

        $db->update('api_keys', ['last_used_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $record['api_key_id']]);

        return true;
    }

    public static function user(): ?array
    {
        return self::$apiUser;
    }

    public static function userId(): ?int
    {
        return self::$apiUser['id'] ?? null;
    }

    private static function extractApiKey(): string
    {
        $apiKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
        if ($apiKey !== '') {
            return $apiKey;
        }

        $authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (preg_match('/^ApiKey\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private static function deny(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
