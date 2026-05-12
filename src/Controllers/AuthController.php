<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\I18n;
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
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /login');
            exit;
        }

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = I18n::translate('flash.enter_email_password');
            header('Location: /login');
            exit;
        }

        if (Auth::attempt($email, $password)) {
            header('Location: /dashboard');
            exit;
        }

        $_SESSION['flash_error'] = I18n::translate('flash.invalid_email_or_password');
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
