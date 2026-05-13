<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\NotificationService;
use App\Core\View;

class UserProfileController
{
    private const DAILY_MAX_HOURS = 9.0;
    private const WEEKLY_MAX_HOURS = 40.0;
    private const MIN_REST_HOURS = 12.0;
    public const DEFAULT_WORK_SLOTS = [
        ['start_time' => '09:00', 'end_time' => '18:00'],
    ];

    private const DAYS_OF_WEEK = [
        0 => 'monday',
        1 => 'tuesday',
        2 => 'wednesday',
        3 => 'thursday',
        4 => 'friday',
        5 => 'saturday',
        6 => 'sunday',
    ];

    public function index(): void
    {
        Auth::requireLogin();

        $db = Database::getInstance();
        $user = Auth::user();
        $prefs = $db->fetchOne(
            'SELECT * FROM user_preferences WHERE user_id = ?',
            [(int) $user['id']]
        );

        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $schedule = self::loadSchedule($db, (int) $user['id']);

        $notifService = new NotificationService();
        $notifPrefs = $notifService->getUserPreferences((int) $user['id']);

        View::render('profile.index', [
            'user' => $user,
            'prefs' => $prefs ?: ['timezone' => 'Europe/Madrid', 'locale' => I18n::getLocale(), 'theme' => 'light'],
            'timezones' => $timezones,
            'supportedLocales' => I18n::getSupportedLocales(),
            'schedule' => $schedule,
            'daysOfWeek' => self::DAYS_OF_WEEK,
            'notifPrefs' => $notifPrefs,
        ]);
    }

    public function savePreferences(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $db = Database::getInstance();

        $timezone = trim($_POST['timezone'] ?? 'Europe/Madrid');
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), true)) {
            $timezone = 'Europe/Madrid';
        }

        $locale = trim($_POST['locale'] ?? 'es');
        if (!in_array($locale, I18n::getSupportedLocales(), true)) {
            $locale = 'es';
        }

        $theme = trim($_POST['theme'] ?? 'light');
        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }

        $db->query(
            'INSERT INTO user_preferences (user_id, timezone, locale, theme) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE timezone = VALUES(timezone), locale = VALUES(locale), theme = VALUES(theme)',
            [$userId, $timezone, $locale, $theme]
        );

        $_SESSION['user_timezone'] = $timezone;
        $_SESSION['lang'] = $locale;
        $_SESSION['user_theme'] = $theme;

        $_SESSION['flash_success'] = I18n::translate('profile.preferences_saved');
        header('Location: /profile');
        exit;
    }

    public function changePassword(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $db = Database::getInstance();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = $db->fetchOne('SELECT password FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['flash_error'] = I18n::translate('profile.current_password_incorrect');
            header('Location: /profile');
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['flash_error'] = I18n::translate('profile.password_min_length');
            header('Location: /profile');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = I18n::translate('profile.passwords_do_not_match');
            header('Location: /profile');
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query('UPDATE users SET password = ? WHERE id = ?', [$hash, $userId]);

        $_SESSION['flash_success'] = I18n::translate('profile.password_changed');
        header('Location: /profile');
        exit;
    }

    public function saveSchedule(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $db = Database::getInstance();

        $error = self::upsertSchedule($db, $userId, $_POST);
        if ($error !== null) {
            $_SESSION['flash_error'] = $error;
            header('Location: /profile');
            exit;
        }

        $_SESSION['flash_success'] = I18n::translate('profile.schedule_saved');
        header('Location: /profile');
        exit;
    }

    /**
     * Admin: show work schedule for a specific employee.
     */
    public function adminSchedule(string $id): void
    {
        Auth::requireAdmin();

        $db = Database::getInstance();
        $employee = $db->fetchOne(
            'SELECT id, first_name, last_name, email FROM users WHERE id = ?',
            [(int) $id]
        );

        if (!$employee) {
            $_SESSION['flash_error'] = I18n::translate('profile.employee_not_found');
            header('Location: /admin/employees');
            exit;
        }

        $schedule = self::loadSchedule($db, (int) $id);

        View::render('profile.schedule', [
            'employee' => $employee,
            'schedule' => $schedule,
            'daysOfWeek' => self::DAYS_OF_WEEK,
            'isAdmin' => true,
        ]);
    }

    /**
     * Admin: save work schedule for a specific employee.
     */
    public function adminSaveSchedule(string $id): void
    {
        Auth::requireAdmin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header("Location: /admin/employees/{$id}/schedule");
            exit;
        }

        $db = Database::getInstance();
        $employee = $db->fetchOne('SELECT id FROM users WHERE id = ?', [(int) $id]);
        if (!$employee) {
            $_SESSION['flash_error'] = I18n::translate('profile.employee_not_found');
            header('Location: /admin/employees');
            exit;
        }

        $error = self::upsertSchedule($db, (int) $id, $_POST);
        if ($error !== null) {
            $_SESSION['flash_error'] = $error;
            header("Location: /admin/employees/{$id}/schedule");
            exit;
        }

        $_SESSION['flash_success'] = I18n::translate('profile.schedule_saved');
        header("Location: /admin/employees/{$id}/schedule");
        exit;
    }

    /**
     * Save user notification preferences from profile page.
     */
    public function saveNotificationPreferences(): void
    {
        Auth::requireLogin();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /profile');
            exit;
        }

        $userId = Auth::id();
        $service = new NotificationService();
        $prefs = [];

        foreach (array_keys(NotificationService::NOTIFICATION_TYPES) as $key) {
            $prefs[$key] = isset($_POST[$key]);
        }

        $service->saveUserPreferences($userId, $prefs);

        $_SESSION['flash_success'] = I18n::translate('notifications.preferences_saved');
        header('Location: /profile');
        exit;
    }

    /**
     * Load the 7-day schedule for a user, keyed by day index (0–6).
     */
    private static function loadSchedule(Database $db, int $userId): array
    {
        $rows = $db->fetchAll(
            'SELECT day_of_week, slot_index, is_working, start_time, end_time
             FROM user_work_schedules
             WHERE user_id = ?
             ORDER BY day_of_week, slot_index',
            [$userId]
        );

        $schedule = [];
        for ($d = 0; $d < 7; $d++) {
            $schedule[$d] = [
                'day_of_week' => $d,
                'is_working' => 0,
                'slots' => [],
            ];
        }

        foreach ($rows as $row) {
            $dayOfWeek = (int) $row['day_of_week'];
            if ($dayOfWeek < 0 || $dayOfWeek > 6 || empty($row['is_working'])) {
                continue;
            }

            $schedule[$dayOfWeek]['is_working'] = 1;
            $schedule[$dayOfWeek]['slots'][] = [
                'start_time' => substr((string) $row['start_time'], 0, 5),
                'end_time' => substr((string) $row['end_time'], 0, 5),
            ];
        }

        // Fill defaults for missing days (Mon–Fri working 09:00–18:00 with 1h break, Sat–Sun off)
        for ($d = 0; $d < 7; $d++) {
            if ($schedule[$d]['slots'] === [] && $d < 5) {
                $schedule[$d]['is_working'] = 1;
                $schedule[$d]['slots'] = self::DEFAULT_WORK_SLOTS;
            }
        }

        ksort($schedule);
        return $schedule;
    }

    /**
     * Insert/update the 7-day schedule from POST data.
     */
    private static function upsertSchedule(Database $db, int $userId, array $post): ?string
    {
        $normalizedSchedule = [];

        for ($d = 0; $d < 7; $d++) {
            $isWorking = !empty($post["working_{$d}"]) ? 1 : 0;

            $startTimes = $post["start_{$d}"] ?? [];
            $endTimes = $post["end_{$d}"] ?? [];

            if (!is_array($startTimes)) {
                $startTimes = [$startTimes];
            }
            if (!is_array($endTimes)) {
                $endTimes = [$endTimes];
            }

            $slots = [];
            $slotsCount = max(count($startTimes), count($endTimes));
            for ($slotIndex = 0; $slotIndex < $slotsCount; $slotIndex++) {
                $startTime = trim((string) ($startTimes[$slotIndex] ?? ''));
                $endTime = trim((string) ($endTimes[$slotIndex] ?? ''));
                if ($startTime === '' && $endTime === '') {
                    continue;
                }

                if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                    $startTime = '09:00';
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                    $endTime = '18:00';
                }

                $slots[] = [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ];
            }

            if ($isWorking === 1 && $slots === []) {
                $slots = self::DEFAULT_WORK_SLOTS;
            }

            $normalizedSchedule[$d] = [
                'slots' => $isWorking === 1 ? $slots : [],
            ];
        }

        $validationError = self::validateScheduleCompliance($normalizedSchedule);
        if ($validationError !== null) {
            return $validationError;
        }

        $db->query('DELETE FROM user_work_schedules WHERE user_id = ?', [$userId]);

        for ($d = 0; $d < 7; $d++) {
            foreach ($normalizedSchedule[$d]['slots'] as $slotIndex => $slot) {
                $db->query(
                    'INSERT INTO user_work_schedules (user_id, day_of_week, slot_index, is_working, start_time, end_time)
                     VALUES (?, ?, ?, 1, ?, ?)',
                    [$userId, $d, $slotIndex, $slot['start_time'], $slot['end_time']]
                );
            }
        }

        return null;
    }

    private static function validateScheduleCompliance(array $schedule): ?string
    {
        $dailyMaxMinutes = (int) round(self::DAILY_MAX_HOURS * 60);
        $weeklyMaxMinutes = (int) round(self::WEEKLY_MAX_HOURS * 60);
        $minRestMinutes = (int) round(self::MIN_REST_HOURS * 60);

        $weeklyMinutes = 0;
        $workingStarts = [];
        $workingEnds = [];

        for ($d = 0; $d < 7; $d++) {
            $slots = $schedule[$d]['slots'] ?? [];
            if ($slots === []) {
                continue;
            }

            $intervals = [];
            $dailyMinutes = 0;
            foreach ($slots as $slot) {
                $startMinutes = self::timeToMinutes($slot['start_time']);
                $endMinutes = self::timeToMinutes($slot['end_time']);
                if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
                    return I18n::translate('profile.schedule_error_invalid_range', [
                        'day' => I18n::translate('profile.day_' . self::DAYS_OF_WEEK[$d]),
                    ]);
                }

                $intervals[] = ['start' => $startMinutes, 'end' => $endMinutes];
            }

            usort($intervals, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);

            for ($i = 0; $i < count($intervals); $i++) {
                if ($i > 0 && $intervals[$i]['start'] < $intervals[$i - 1]['end']) {
                    return I18n::translate('profile.schedule_error_invalid_range', [
                        'day' => I18n::translate('profile.day_' . self::DAYS_OF_WEEK[$d]),
                    ]);
                }

                $dailyMinutes += $intervals[$i]['end'] - $intervals[$i]['start'];
            }

            if (count($intervals) === 1) {
                // For single-slot schedules, apply up to 60 minutes break (capped to actual worked minutes), as in profile UI.
                $dailyMinutes -= min(60, $dailyMinutes);
            }

            if ($dailyMinutes > $dailyMaxMinutes) {
                return I18n::translate('profile.schedule_error_daily_max', [
                    'day' => I18n::translate('profile.day_' . self::DAYS_OF_WEEK[$d]),
                    'max' => (string) self::DAILY_MAX_HOURS,
                ]);
            }

            $weeklyMinutes += $dailyMinutes;
            $workingStarts[$d] = ($d * 1440) + $intervals[0]['start'];
            $workingEnds[$d] = ($d * 1440) + $intervals[count($intervals) - 1]['end'];
        }

        if ($weeklyMinutes > $weeklyMaxMinutes) {
            return I18n::translate('profile.schedule_error_weekly_max', [
                'max' => (string) self::WEEKLY_MAX_HOURS,
            ]);
        }

        for ($d = 0; $d < 7; $d++) {
            if (!isset($workingStarts[$d])) {
                continue;
            }

            $previousDay = null;
            for ($i = 1; $i <= 7; $i++) {
                $candidate = ($d - $i + 7) % 7;
                if (isset($workingEnds[$candidate])) {
                    $previousDay = $candidate;
                    break;
                }
            }

            if ($previousDay === null || $previousDay === $d) {
                continue;
            }

            $restMinutes = $workingStarts[$d] - $workingEnds[$previousDay];
            if ($previousDay > $d) {
                $restMinutes += 7 * 1440;
            }

            if ($restMinutes < $minRestMinutes) {
                return I18n::translate('profile.schedule_error_min_rest', [
                    'day' => I18n::translate('profile.day_' . self::DAYS_OF_WEEK[$d]),
                    'prev_day' => I18n::translate('profile.day_' . self::DAYS_OF_WEEK[$previousDay]),
                    'min' => (string) self::MIN_REST_HOURS,
                ]);
            }
        }

        return null;
    }

    private static function timeToMinutes(string $time): ?int
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }
}
