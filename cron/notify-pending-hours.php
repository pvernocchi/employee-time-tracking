#!/usr/bin/env php
<?php
/**
 * Daily Pending-Hours Reminder for Managers
 *
 * This script should be executed via cron every hour (or at the desired
 * granularity). It checks each manager's configured timezone and sends
 * the reminder only when it is 09:00 in that timezone.
 *
 * Recommended crontab entry (run every hour on the hour):
 *   0 * * * * php /path/to/cron/notify-pending-hours.php
 *
 * Alternatively, if the server timezone matches all managers' timezones,
 * you can run it once a day at 09:00:
 *   0 9 * * * php /path/to/cron/notify-pending-hours.php --force
 *
 * Options:
 *   --force   Skip the 09:00 timezone check and send reminders immediately.
 */

$basePath = dirname(__DIR__);

require_once $basePath . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\NotificationService;
use App\Core\SmtpMailer;
use App\Core\SetupManager;

// Load configuration
$setupManager = new SetupManager($basePath);
$config = $setupManager->loadConfig();

date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Madrid');

// Connect to the database
Database::getInstance($config['database']);

// Initialise SMTP secret
SmtpMailer::setSecret($config['smtp']['secret'] ?? '');

$force = in_array('--force', $argv ?? [], true);

$service = new NotificationService();

if ($force) {
    $sent = $service->sendDailyPendingHoursReminders();
} else {
    $sent = $service->sendDailyPendingHoursRemindersAtLocalNine();
}

echo date('Y-m-d H:i:s') . " — Pending-hours reminders sent: {$sent}\n";
