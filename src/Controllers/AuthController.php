<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        View::render('auth.login', [], 'auth');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /login');
            exit;
        }

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Please enter email and password.';
            header('Location: /login');
            exit;
        }

        if (Auth::attempt($email, $password)) {
            header('Location: /dashboard');
            exit;
        }

        $_SESSION['flash_error'] = 'Invalid email or password.';
        header('Location: /login');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
