<?php

namespace App\Core;

use PHPMailer\PHPMailer\Exception as MailerException;

class NotificationService
{
    private SmtpMailer $mailer;

    public function __construct(?SmtpMailer $mailer = null)
    {
        $this->mailer = $mailer ?? new SmtpMailer(dirname(__DIR__, 2));
    }

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

        $db = Database::getInstance();

        $managers = $this->getManagersForEmployee($db, (int) $employee['id']);

        if (empty($managers)) {
            return;
        }

        $employeeName = htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
        $leaveType = ucfirst(str_replace('_', ' ', $leaveRequest['leave_type']));
        $startDate = date('M j, Y', strtotime($leaveRequest['start_date']));
        $endDate = date('M j, Y', strtotime($leaveRequest['end_date']));
        $days = (strtotime($leaveRequest['end_date']) - strtotime($leaveRequest['start_date'])) / 86400 + 1;
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
        if (!$this->mailer->isEnabled()) {
            return 0;
        }

        $db = Database::getInstance();
        $emailsSent = 0;

        $managers = $db->fetchAll(
            "SELECT u.id, u.email, u.first_name, u.last_name
             FROM users u
             WHERE u.role IN ('manager', 'admin') AND u.is_active = 1"
        );

        foreach ($managers as $manager) {
            $tz = $this->getManagerTimezone($db, (int) $manager['id']);
            $mgrTz = new \DateTimeZone($tz);
            $now = new \DateTimeImmutable('now', $mgrTz);

            // Only proceed if the current hour in the manager's timezone is 9
            if ((int) $now->format('G') !== 9) {
                continue;
            }

            $yesterday = $now->modify('-1 day')->format('Y-m-d');

            $pendingEntries = $db->fetchAll(
                "SELECT te.id, te.clock_in, te.clock_out, te.break_minutes, te.status,
                        u.first_name, u.last_name
                 FROM time_entries te
                 JOIN users u ON te.user_id = u.id
                 WHERE u.manager_id = ?
                   AND DATE(te.clock_in) = ?
                   AND te.status IN ('completed', 'edited')
                 ORDER BY u.last_name, u.first_name, te.clock_in",
                [$manager['id'], $yesterday]
            );

            if (empty($pendingEntries)) {
                continue;
            }

            $emailsSent += $this->sendPendingHoursEmail($manager, $pendingEntries, $yesterday);
        }

        return $emailsSent;
    }

    /**
     * Send a daily reminder to each manager who has employees with pending
     * (non-approved) completed time entries from yesterday.
     *
     * Intended to be called from a cron job.
     */
    public function sendDailyPendingHoursReminders(): int
    {
        if (!$this->mailer->isEnabled()) {
            return 0;
        }

        $db = Database::getInstance();
        $emailsSent = 0;

        // Find all active managers and admins
        $managers = $db->fetchAll(
            "SELECT u.id, u.email, u.first_name, u.last_name
             FROM users u
             WHERE u.role IN ('manager', 'admin') AND u.is_active = 1"
        );

        foreach ($managers as $manager) {
            // Determine the manager's timezone
            $tz = $this->getManagerTimezone($db, (int) $manager['id']);

            // Calculate "yesterday" in the manager's timezone
            $mgrTz = new \DateTimeZone($tz);
            $now = new \DateTimeImmutable('now', $mgrTz);
            $yesterday = $now->modify('-1 day')->format('Y-m-d');

            // Fetch completed (but not approved) time entries from yesterday
            // for employees managed by this manager
            $pendingEntries = $db->fetchAll(
                "SELECT te.id, te.clock_in, te.clock_out, te.break_minutes, te.status,
                        u.first_name, u.last_name
                 FROM time_entries te
                 JOIN users u ON te.user_id = u.id
                 WHERE u.manager_id = ?
                   AND DATE(te.clock_in) = ?
                   AND te.status IN ('completed', 'edited')
                 ORDER BY u.last_name, u.first_name, te.clock_in",
                [$manager['id'], $yesterday]
            );

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
