<?php

namespace App\Controllers;

use App\Core\NotificationService;
use App\Core\SmtpMailer;
use App\Core\View;
use PHPMailer\PHPMailer\Exception as MailerException;

class AdminSettingsController
{
    public function smtpSettings(): void
    {
        $mailer = new SmtpMailer(dirname(__DIR__, 2));
        $settings = $mailer->getSettings();

        View::render('admin.settings.smtp', [
            'settings' => $settings,
        ]);
    }

    public function saveSmtpSettings(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/settings/smtp');
            exit;
        }

        $port = (int) ($_POST['smtp_port'] ?? 587);
        if ($port < 1 || $port > 65535) {
            $_SESSION['flash_error'] = 'SMTP port must be between 1 and 65535.';
            header('Location: /admin/settings/smtp');
            exit;
        }

        $fromEmail = trim($_POST['smtp_from_email'] ?? '');
        if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'The "From" email address is not valid.';
            header('Location: /admin/settings/smtp');
            exit;
        }

        $allowedEncryptions = ['none', 'tls', 'ssl'];
        $encryption = $_POST['smtp_encryption'] ?? 'tls';
        if (!in_array($encryption, $allowedEncryptions, true)) {
            $encryption = 'tls';
        }

        // Keep existing password if the field is left blank
        $password = $_POST['smtp_password'] ?? '';
        if ($password === '') {
            $mailer = new SmtpMailer(dirname(__DIR__, 2));
            $existingSettings = $mailer->getSettings();
            $password = $existingSettings['smtp_password'];
        }

        $settings = [
            'smtp_enabled'     => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host'        => trim($_POST['smtp_host'] ?? ''),
            'smtp_port'        => (string) $port,
            'smtp_encryption'  => $encryption,
            'smtp_auth'        => isset($_POST['smtp_auth']) ? '1' : '0',
            'smtp_username'    => trim($_POST['smtp_username'] ?? ''),
            'smtp_password'    => $password,
            'smtp_from_email'  => $fromEmail,
            'smtp_from_name'   => trim($_POST['smtp_from_name'] ?? ''),
            'smtp_log_enabled' => isset($_POST['smtp_log_enabled']) ? '1' : '0',
        ];

        SmtpMailer::saveSettings($settings);

        $_SESSION['flash_success'] = 'SMTP settings saved successfully.';
        header('Location: /admin/settings/smtp');
        exit;
    }

    public function testSmtp(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/settings/smtp');
            exit;
        }

        $recipient = trim($_POST['test_email'] ?? '');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid recipient email address for the test.';
            header('Location: /admin/settings/smtp');
            exit;
        }

        try {
            $mailer = new SmtpMailer(dirname(__DIR__, 2));

            if (!$mailer->isEnabled()) {
                $_SESSION['flash_error'] = 'SMTP is not enabled. Please save the settings with SMTP enabled before testing.';
                header('Location: /admin/settings/smtp');
                exit;
            }

            $mailer->send(
                $recipient,
                'SMTP Test - Employee Time Tracker',
                '<p>This is a test email from <strong>Employee Time Tracker</strong>.</p>'
                . '<p>If you receive this message, your SMTP configuration is working correctly.</p>',
                'This is a test email from Employee Time Tracker. If you receive this message, your SMTP configuration is working correctly.'
            );

            $_SESSION['flash_success'] = 'Test email sent successfully to ' . htmlspecialchars($recipient) . '.';
        } catch (MailerException $e) {
            $_SESSION['flash_error'] = 'Failed to send test email: ' . $e->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Unexpected error: ' . $e->getMessage();
        }

        header('Location: /admin/settings/smtp');
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Notification settings                                              */
    /* ------------------------------------------------------------------ */

    public function notificationSettings(): void
    {
        $service = new NotificationService();

        View::render('admin.settings.notifications', [
            'settings' => $service->getAdminSettings(),
        ]);
    }

    public function saveNotificationSettings(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = \App\Core\I18n::translate('flash.invalid_request_try_again');
            header('Location: /admin/settings/notifications');
            exit;
        }

        $settings = [];
        foreach (array_keys(NotificationService::NOTIFICATION_TYPES) as $key) {
            $settings[$key] = isset($_POST[$key]) ? '1' : '0';
        }

        $service = new NotificationService();
        $service->saveAdminSettings($settings);

        $_SESSION['flash_success'] = \App\Core\I18n::translate('notifications.settings_saved');
        header('Location: /admin/settings/notifications');
        exit;
    }
}
