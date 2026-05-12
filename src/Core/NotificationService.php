<?php

namespace App\Core;

class NotificationService
{
    /** Known notification types and their default state. */
    public const NOTIFICATION_TYPES = [
        'clock_in_reminder'  => '0',
        'clock_out_reminder' => '0',
    ];

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ------------------------------------------------------------------ */
    /*  Admin-level settings                                               */
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
    /*  Notification processing (called from CLI cron)                     */
    /* ------------------------------------------------------------------ */

    /**
     * Process pending notifications for all active users.
     * Returns the number of notifications sent.
     */
    public function processNotifications(SmtpMailer $mailer): int
    {
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

    /* ------------------------------------------------------------------ */
    /*  Individual notification senders                                    */
    /* ------------------------------------------------------------------ */

    private function sendClockInReminder(SmtpMailer $mailer, array $user, string $today, array $schedule): int
    {
        $userId = (int) $user['id'];

        // Already sent today?
        if ($this->alreadySent($userId, 'clock_in_reminder', $today)) {
            return 0;
        }

        // Has the user already clocked in today?
        $entry = $this->db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ?',
            [$userId, $today]
        );

        if ($entry) {
            return 0; // already clocked in
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

        // Already sent today?
        if ($this->alreadySent($userId, 'clock_out_reminder', $today)) {
            return 0;
        }

        // Has the user clocked out today? Check for active (no clock_out) entries.
        $active = $this->db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL AND status = "active"',
            [$userId, $today]
        );

        // If no active entry, check if they have any completed entry today (already clocked out)
        if (!$active) {
            return 0; // either not clocked in at all, or already clocked out
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

    /* ------------------------------------------------------------------ */
    /*  Deduplication helpers                                              */
    /* ------------------------------------------------------------------ */

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
}
