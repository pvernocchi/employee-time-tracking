<?php

namespace App\Core;

use PHPMailer\PHPMailer\Exception as MailerException;

class NotificationService
{
    /** Known notification types and their default state. */
    public const NOTIFICATION_TYPES = [
        'clock_in_reminder'  => '0',
        'clock_out_reminder' => '0',
    ];

    private const REMINDER_HOUR = 9;
    private const SECONDS_PER_DAY = 86400;

    private Database $db;
    private SmtpMailer $mailer;

    public function __construct(?SmtpMailer $mailer = null)
    {
        $this->db = Database::getInstance();
        $this->mailer = $mailer ?? new SmtpMailer(dirname(__DIR__, 2));
    }

    /* ------------------------------------------------------------------ */
    /*  Admin-level settings (clock-in/out reminders)                      */
    /* ------------------------------------------------------------------ */

    /**
     * Get all admin notification settings merged with defaults.
     *
     * @return array<string,string>
     */
    public function getAdminSettings(): array
    {
        $defaults = self::NOTIFICATION_TYPES;

        try {
            $rows = $this->db->fetchAll('SELECT setting_key, setting_value FROM notification_settings');
            $loaded = [];
            foreach ($rows as $row) {
                $loaded[$row['setting_key']] = $row['setting_value'];
            }
            return array_merge($defaults, $loaded);
        } catch (\Throwable) {
            return $defaults;
        }
    }

    /**
     * Save admin-level notification settings.
     *
     * @param array<string,string> $settings
     */
    public function saveAdminSettings(array $settings): void
    {
        $allowed = array_keys(self::NOTIFICATION_TYPES);

        foreach ($allowed as $key) {
            $value = $settings[$key] ?? '0';
            $this->db->query(
                'INSERT INTO notification_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$key, $value]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Per-user notification preferences                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Get user notification preferences merged with defaults.
     *
     * @return array<string,bool>
     */
    public function getUserPreferences(int $userId): array
    {
        $defaults = [];
        foreach (self::NOTIFICATION_TYPES as $key => $_) {
            $defaults[$key] = true; // enabled by default
        }

        try {
            $rows = $this->db->fetchAll(
                'SELECT notification_key, enabled FROM user_notification_preferences WHERE user_id = ?',
                [$userId]
            );
            foreach ($rows as $row) {
                if (array_key_exists($row['notification_key'], $defaults)) {
                    $defaults[$row['notification_key']] = (bool) $row['enabled'];
                }
            }
        } catch (\Throwable) {
            // table may not exist yet
        }

        return $defaults;
    }

    /**
     * Save user notification preferences.
     *
     * @param array<string,bool> $prefs
     */
    public function saveUserPreferences(int $userId, array $prefs): void
    {
        $allowed = array_keys(self::NOTIFICATION_TYPES);

        foreach ($allowed as $key) {
            $enabled = !empty($prefs[$key]) ? 1 : 0;
            $this->db->query(
                'INSERT INTO user_notification_preferences (user_id, notification_key, enabled) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)',
                [$userId, $key, $enabled]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Clock-in / clock-out reminder processing (called from CLI cron)    */
    /* ------------------------------------------------------------------ */

    /**
     * Process pending clock-in / clock-out reminders for all active users.
     * Returns the number of notifications sent.
     */
    public function processNotifications(?SmtpMailer $mailer = null): int
    {
        $mailer = $mailer ?? $this->mailer;

        $adminSettings = $this->getAdminSettings();
        $today = date('Y-m-d');
        $now = date('H:i');
        $dayOfWeek = (int) date('N') - 1; // 0=Mon … 6=Sun (matches user_work_schedules)
        $sent = 0;

        $users = $this->db->fetchAll(
            'SELECT id, email, first_name, last_name FROM users WHERE is_active = 1'
        );

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $userPrefs = $this->getUserPreferences($userId);

            $schedule = $this->db->fetchOne(
                'SELECT is_working, start_time, end_time FROM user_work_schedules WHERE user_id = ? AND day_of_week = ?',
                [$userId, $dayOfWeek]
            );

            if (!$schedule) {
                // Use defaults: Mon-Fri working 09:00-17:00
                $schedule = [
                    'is_working' => $dayOfWeek < 5 ? 1 : 0,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ];
            }

            if (empty($schedule['is_working'])) {
                continue;
            }

            // Clock-in reminder
            if ($adminSettings['clock_in_reminder'] === '1' && !empty($userPrefs['clock_in_reminder'])) {
                $reminderTime = date('H:i', strtotime($schedule['start_time'] . ' +10 minutes'));
                if ($now >= $reminderTime) {
                    $sent += $this->sendClockInReminder($mailer, $user, $today, $schedule);
                }
            }

            // Clock-out reminder
            if ($adminSettings['clock_out_reminder'] === '1' && !empty($userPrefs['clock_out_reminder'])) {
                $reminderTime = date('H:i', strtotime($schedule['end_time'] . ' +10 minutes'));
                if ($now >= $reminderTime) {
                    $sent += $this->sendClockOutReminder($mailer, $user, $today, $schedule);
                }
            }
        }

        return $sent;
    }

    private function sendClockInReminder(SmtpMailer $mailer, array $user, string $today, array $schedule): int
    {
        $userId = (int) $user['id'];

        if ($this->alreadySent($userId, 'clock_in_reminder', $today)) {
            return 0;
        }

        $entry = $this->db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ?',
            [$userId, $today]
        );

        if ($entry) {
            return 0;
        }

        $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $startTime = htmlspecialchars(substr($schedule['start_time'], 0, 5));
        $subject = 'Reminder: Clock In – ' . $user['first_name'];
        $body = "<p>Hello {$name},</p>"
            . "<p>Your shift started at <strong>{$startTime}</strong> today and you have not clocked in yet.</p>"
            . '<p>Please clock in as soon as possible.</p>';

        try {
            $mailer->send($user['email'], $subject, $body);
            $this->recordSent($userId, 'clock_in_reminder', $today);
            return 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function sendClockOutReminder(SmtpMailer $mailer, array $user, string $today, array $schedule): int
    {
        $userId = (int) $user['id'];

        if ($this->alreadySent($userId, 'clock_out_reminder', $today)) {
            return 0;
        }

        $active = $this->db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL AND status = "active"',
            [$userId, $today]
        );

        if (!$active) {
            return 0;
        }

        $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $endTime = htmlspecialchars(substr($schedule['end_time'], 0, 5));
        $subject = 'Reminder: Clock Out – ' . $user['first_name'];
        $body = "<p>Hello {$name},</p>"
            . "<p>Your shift ended at <strong>{$endTime}</strong> today and you have not clocked out yet.</p>"
            . '<p>Please clock out as soon as possible.</p>';

        try {
            $mailer->send($user['email'], $subject, $body);
            $this->recordSent($userId, 'clock_out_reminder', $today);
            return 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function alreadySent(int $userId, string $key, string $date): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM notification_log WHERE user_id = ? AND notification_key = ? AND sent_date = ?',
            [$userId, $key, $date]
        );
        return $row !== null;
    }

    private function recordSent(int $userId, string $key, string $date): void
    {
        $this->db->query(
            'INSERT IGNORE INTO notification_log (user_id, notification_key, sent_date) VALUES (?, ?, ?)',
            [$userId, $key, $date]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Manager notifications (leave requests, pending hours)              */
    /* ------------------------------------------------------------------ */

    /**
     * Notify the manager(s) of a new or resubmitted leave request.
     *
     * @param array $leaveRequest  Row from leave_requests (must contain leave_type, start_date, end_date, reason)
     * @param array $employee      Row from users (the employee who submitted)
     */
    public function notifyManagerLeaveRequest(array $leaveRequest, array $employee): void
    {
        if (!$this->mailer->isEnabled()) {
            return;
        }

        $managers = $this->getManagersForEmployee($this->db, (int) $employee['id']);

        if (empty($managers)) {
            return;
        }

        $employeeName = htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
        $leaveType = ucfirst(str_replace('_', ' ', $leaveRequest['leave_type']));
        $startDate = date('M j, Y', strtotime($leaveRequest['start_date']));
        $endDate = date('M j, Y', strtotime($leaveRequest['end_date']));
        $days = (strtotime($leaveRequest['end_date']) - strtotime($leaveRequest['start_date'])) / self::SECONDS_PER_DAY  + 1;
        $reason = htmlspecialchars($leaveRequest['reason'] ?? '—');

        $subject = "New Leave Request from {$employeeName}";

        $body = <<<HTML
<h2>New Leave Request</h2>
<p><strong>{$employeeName}</strong> has submitted a leave request that requires your review.</p>
<table style="border-collapse:collapse;width:100%;max-width:500px;">
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">Employee</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$employeeName}</td></tr>
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">Type</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$leaveType}</td></tr>
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">From</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$startDate}</td></tr>
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">To</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$endDate}</td></tr>
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">Days</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$days}</td></tr>
  <tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">Reason</td>
      <td style="padding:6px 12px;border:1px solid #ddd;">{$reason}</td></tr>
</table>
<p style="margin-top:16px;">Please log in to review and approve or reject this request.</p>
HTML;

        foreach ($managers as $manager) {
            try {
                $this->mailer->send(
                    [$manager['email'] => $manager['first_name'] . ' ' . $manager['last_name']],
                    $subject,
                    $body
                );
            } catch (MailerException $e) {
                // Notification failures should not break the application flow.
                // The SmtpMailer already logs the error.
            }
        }
    }

    /**
     * Timezone-aware variant: only send the reminder when it is currently
     * 09:00 (hour = 9) in the manager's configured timezone.
     *
     * This is designed to be called from a cron job that runs every hour.
     */
    public function sendDailyPendingHoursRemindersAtLocalNine(): int
    {
        return $this->processPendingHoursReminders(true);
    }

    /**
     * Send a daily reminder to each manager who has employees with pending
     * (non-approved) completed time entries from yesterday.
     *
     * Intended to be called from a cron job.
     */
    public function sendDailyPendingHoursReminders(): int
    {
        return $this->processPendingHoursReminders(false);
    }

    /**
     * Core logic shared by both reminder methods.
     *
     * @param bool $checkHour When true, only send if the manager's local hour equals REMINDER_HOUR.
     */
    private function processPendingHoursReminders(bool $checkHour): int
    {
        if (!$this->mailer->isEnabled()) {
            return 0;
        }

        $emailsSent = 0;

        $managers = $this->getActiveManagers($this->db);

        foreach ($managers as $manager) {
            $tz = $this->getManagerTimezone($this->db, (int) $manager['id']);
            $mgrTz = new \DateTimeZone($tz);
            $now = new \DateTimeImmutable('now', $mgrTz);

            if ($checkHour && (int) $now->format('G') !== self::REMINDER_HOUR) {
                continue;
            }

            $yesterday = $now->modify('-1 day')->format('Y-m-d');

            $pendingEntries = $this->getPendingEntriesForManager($this->db, (int) $manager['id'], $yesterday);

            if (empty($pendingEntries)) {
                continue;
            }

            $emailsSent += $this->sendPendingHoursEmail($manager, $pendingEntries, $yesterday);
        }

        return $emailsSent;
    }

    /**
     * Build and send the pending-hours summary email for a single manager.
     */
    private function sendPendingHoursEmail(array $manager, array $entries, string $date): int
    {
        $formattedDate = date('M j, Y', strtotime($date));
        $subject = "Pending Hours to Approve – {$formattedDate}";

        $rows = '';
        $totalHours = 0;
        foreach ($entries as $entry) {
            $name = htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']);
            $clockIn = date('H:i', strtotime($entry['clock_in']));
            $clockOut = $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : '—';
            $breakMin = (int) $entry['break_minutes'];
            $hours = 0;
            if ($entry['clock_out']) {
                $hours = (strtotime($entry['clock_out']) - strtotime($entry['clock_in'])) / 3600 - ($breakMin / 60);
                if ($hours < 0) {
                    $hours = 0;
                }
            }
            $totalHours += $hours;
            $hoursFormatted = round($hours, 2);
            $status = ucfirst($entry['status']);

            $rows .= <<<HTML
  <tr>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$name}</td>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$clockIn}</td>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$clockOut}</td>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$breakMin} min</td>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$hoursFormatted}h</td>
    <td style="padding:6px 12px;border:1px solid #ddd;">{$status}</td>
  </tr>
HTML;
        }

        $totalFormatted = round($totalHours, 2);
        $entryCount = count($entries);

        $body = <<<HTML
<h2>Pending Hours – {$formattedDate}</h2>
<p>You have <strong>{$entryCount}</strong> time entries pending approval from yesterday.</p>
<table style="border-collapse:collapse;width:100%;">
  <thead>
    <tr style="background:#f5f5f5;">
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Employee</th>
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Clock In</th>
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Clock Out</th>
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Break</th>
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Hours</th>
      <th style="padding:6px 12px;border:1px solid #ddd;text-align:left;">Status</th>
    </tr>
  </thead>
  <tbody>
    {$rows}
  </tbody>
  <tfoot>
    <tr style="background:#f5f5f5;">
      <td colspan="4" style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">Total</td>
      <td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold;">{$totalFormatted}h</td>
      <td style="padding:6px 12px;border:1px solid #ddd;"></td>
    </tr>
  </tfoot>
</table>
<p style="margin-top:16px;">Please log in to review and approve these hours.</p>
HTML;

        try {
            $this->mailer->send(
                [$manager['email'] => $manager['first_name'] . ' ' . $manager['last_name']],
                $subject,
                $body
            );
            return 1;
        } catch (MailerException $e) {
            return 0;
        }
    }

    /**
     * Get all active managers and admins.
     */
    private function getActiveManagers(Database $db): array
    {
        return $db->fetchAll(
            "SELECT u.id, u.email, u.first_name, u.last_name
             FROM users u
             WHERE u.role IN ('manager', 'admin') AND u.is_active = 1"
        );
    }

    /**
     * Fetch completed (non-approved) time entries for a manager's employees on a given date.
     */
    private function getPendingEntriesForManager(Database $db, int $managerId, string $date): array
    {
        return $db->fetchAll(
            "SELECT te.id, te.clock_in, te.clock_out, te.break_minutes, te.status,
                    u.first_name, u.last_name
             FROM time_entries te
             JOIN users u ON te.user_id = u.id
             WHERE u.manager_id = ?
               AND DATE(te.clock_in) = ?
               AND te.status IN ('completed', 'edited')
             ORDER BY u.last_name, u.first_name, te.clock_in",
            [$managerId, $date]
        );
    }

    /**
     * Get the direct and indirect managers for a given employee.
     *
     * Returns the direct manager and, if the direct manager also has a
     * manager, includes that second-level manager as well (matching the
     * existing leave-approval hierarchy used in LeaveController).
     *
     * @return list<array{id:int,email:string,first_name:string,last_name:string}>
     */
    private function getManagersForEmployee(Database $db, int $employeeId): array
    {
        return $db->fetchAll(
            "SELECT DISTINCT m.id, m.email, m.first_name, m.last_name
             FROM users e
             JOIN users m ON m.id = e.manager_id
             WHERE e.id = ? AND m.is_active = 1
             UNION
             SELECT DISTINCT m2.id, m2.email, m2.first_name, m2.last_name
             FROM users e
             JOIN users m ON m.id = e.manager_id
             JOIN users m2 ON m2.id = m.manager_id
             WHERE e.id = ? AND m2.is_active = 1",
            [$employeeId, $employeeId]
        );
    }

    /**
     * Get the timezone configured for a manager, falling back to Europe/Madrid.
     */
    private function getManagerTimezone(Database $db, int $userId): string
    {
        $pref = $db->fetchOne(
            'SELECT timezone FROM user_preferences WHERE user_id = ?',
            [$userId]
        );

        return $pref['timezone'] ?? 'Europe/Madrid';
    }
}
