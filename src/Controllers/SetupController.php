<?php

namespace App\Controllers;

use App\Core\SetupManager;
use App\Core\View;

class SetupController
{
    private SetupManager $setupManager;

    public function __construct()
    {
        $this->setupManager = new SetupManager(dirname(__DIR__, 2));
    }

    public function install(): void
    {
        $status = $this->setupManager->getStatus();

        if ($status['configExists'] && $status['databaseConnected'] && $status['needsUpgrade']) {
            header('Location: /install/upgrade');
            exit;
        }

        if ($status['configExists'] && $status['databaseConnected'] && $status['isInstalled'] && !$status['needsUpgrade']) {
            header('Location: /login');
            exit;
        }

        $this->renderInstall($status, $_SESSION['setup_old'] ?? []);
        unset($_SESSION['setup_old']);
    }

    public function processInstall(): void
    {
        $this->validateCsrf();

        try {
            $this->setupManager->install($_POST);
            unset($_SESSION['setup_old']);
            $_SESSION['flash_success'] = 'Setup completed successfully. You can now sign in.';
            header('Location: /login');
            exit;
        } catch (\RuntimeException $e) {
            $_SESSION['setup_old'] = $this->sanitizeOldInput($_POST);
            $status = $this->setupManager->getStatus();
            $this->renderInstall($status, $this->sanitizeOldInput($_POST), $e->getMessage());
        }
    }

    public function upgrade(): void
    {
        $status = $this->setupManager->getStatus();

        if (!$status['configExists']) {
            header('Location: /install');
            exit;
        }

        if (!$status['databaseConnected']) {
            header('Location: /install');
            exit;
        }

        if (!$status['needsUpgrade']) {
            $_SESSION['flash_success'] = 'No database upgrades are pending.';
            header('Location: /login');
            exit;
        }

        View::render('setup.upgrade', [
            'title' => 'Upgrade Employee Time Tracker',
            'bodyClass' => 'auth-body',
            'containerClass' => 'auth-container auth-container-wide',
            'status' => $status,
        ], 'auth');
    }

    public function processUpgrade(): void
    {
        $this->validateCsrf();

        try {
            $this->setupManager->upgrade();
            $_SESSION['flash_success'] = 'Database upgrade completed successfully.';
            header('Location: /login');
            exit;
        } catch (\RuntimeException $e) {
            $status = $this->setupManager->getStatus();
            View::render('setup.upgrade', [
                'title' => 'Upgrade Employee Time Tracker',
                'bodyClass' => 'auth-body',
                'containerClass' => 'auth-container auth-container-wide',
                'status' => $status,
                'error' => $e->getMessage(),
            ], 'auth');
        }
    }

    private function renderInstall(array $status, array $old = [], ?string $error = null): void
    {
        $defaults = $this->setupManager->loadConfig();

        View::render('setup.install', [
            'title' => 'Set Up Employee Time Tracker',
            'bodyClass' => 'auth-body',
            'containerClass' => 'auth-container auth-container-wide',
            'status' => $status,
            'defaults' => $defaults,
            'old' => $old,
            'error' => $error,
        ], 'auth');
    }

    private function validateCsrf(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? null)) {
            throw new \RuntimeException('Invalid request. Please refresh the page and try again.');
        }
    }

    private function sanitizeOldInput(array $input): array
    {
        unset($input['admin_password'], $input['db_pass'], $input['csrf_token']);
        return $input;
    }
}
