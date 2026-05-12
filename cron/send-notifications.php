#!/usr/bin/env php
<?php
/**
 * Send pending email notifications.
 *
 * This script is meant to be executed periodically via cron (e.g. every 5 minutes).
 * Example crontab entry:
 *   0/5 * * * * /usr/bin/php /path/to/cron/send-notifications.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\NotificationService;
use App\Core\SmtpMailer;

// Load config
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "Configuration file not found. Run the installer first.\n");
    exit(1);
}

$config = require $configPath;

date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Madrid');

// Initialize database
Database::getInstance($config['database']);

// Initialize SMTP encryption secret
SmtpMailer::setSecret($config['smtp']['secret'] ?? '');

$mailer = new SmtpMailer(dirname(__DIR__));

if (!$mailer->isEnabled()) {
    fwrite(STDERR, "SMTP is not enabled. Configure SMTP in Admin → Settings → SMTP first.\n");
    exit(0);
}

$service = new NotificationService();
$sent = $service->processNotifications($mailer);

echo date('Y-m-d H:i:s') . " – Notifications sent: {$sent}\n";
