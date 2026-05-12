<?php

namespace App\Core;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();
            return true;
        }

        return false;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isManager(): bool
    {
        return in_array(self::role(), ['admin', 'manager']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function requireLogin(): bool
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
        return true;
    }

    public static function requireAdmin(): bool
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '<h1>403 - Access Denied</h1>';
            exit;
        }
        return true;
    }

    public static function requireManager(): bool
    {
        self::requireLogin();
        if (!self::isManager()) {
            http_response_code(403);
            echo '<h1>403 - Access Denied</h1>';
            exit;
        }
        return true;
    }
}
