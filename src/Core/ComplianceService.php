<?php

namespace App\Core;

class ComplianceService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function checkDailyHours(int $userId, string $date): array
    {
        $warnings = [];
        $entries = $this->db->fetchAll(
            'SELECT clock_in, clock_out, break_minutes FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) = ? AND clock_out IS NOT NULL',
            [$userId, $date]
        );

        $totalMinutes = 0;
        foreach ($entries as $entry) {
            $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
            $totalMinutes += ($diff / 60) - $entry['break_minutes'];
        }

        $totalHours = $totalMinutes / 60;
        if ($totalHours > 9) {
            $warnings[] = [
                'type' => 'daily_hours_exceeded',
                'message' => "Jornada diaria excedida: " . round($totalHours, 2) . "h trabajadas (máximo legal: 9h)",
                'severity' => 'warning',
                'date' => $date,
            ];
        }

        return $warnings;
    }

    public function checkWeeklyHours(int $userId, string $weekStart): array
    {
        $warnings = [];
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $result = $this->db->fetchOne(
            'SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, clock_out) - break_minutes) as total_minutes
             FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) >= ? AND DATE(clock_in) <= ? AND clock_out IS NOT NULL',
            [$userId, $weekStart, $weekEnd]
        );

        $totalHours = ($result['total_minutes'] ?? 0) / 60;
        if ($totalHours > 40) {
            $warnings[] = [
                'type' => 'weekly_hours_exceeded',
                'message' => "Jornada semanal excedida: " . round($totalHours, 2) . "h trabajadas (máximo legal: 40h)",
                'severity' => 'warning',
                'week_start' => $weekStart,
            ];
        }

        return $warnings;
    }

    public function checkAnnualOvertime(int $userId, int $year): array
    {
        $warnings = [];
        $overtime = $this->getOvertimeHoursForYear($userId, $year);

        if ($overtime >= 80) {
            $warnings[] = [
                'type' => 'annual_overtime_exceeded',
                'message' => "Límite anual de horas extra SUPERADO: " . round($overtime, 2) . "h (máximo legal: 80h)",
                'severity' => 'danger',
            ];
        } elseif ($overtime >= 60) {
            $warnings[] = [
                'type' => 'annual_overtime_approaching',
                'message' => "Acercándose al límite anual de horas extra: " . round($overtime, 2) . "h de 80h máximo",
                'severity' => 'warning',
            ];
        }

        return $warnings;
    }

    public function checkRestBetweenDays(int $userId, string $date): array
    {
        $warnings = [];
        $previousDay = date('Y-m-d', strtotime($date . ' -1 day'));

        $lastEntry = $this->db->fetchOne(
            'SELECT clock_out FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) = ? AND clock_out IS NOT NULL
             ORDER BY clock_out DESC LIMIT 1',
            [$userId, $previousDay]
        );

        if ($lastEntry) {
            $now = date('Y-m-d H:i:s');
            $hoursSinceLastClockOut = (strtotime($now) - strtotime($lastEntry['clock_out'])) / 3600;

            if ($hoursSinceLastClockOut < 12) {
                $warnings[] = [
                    'type' => 'insufficient_rest',
                    'message' => "Descanso insuficiente entre jornadas: " . round($hoursSinceLastClockOut, 1) . "h (mínimo legal: 12h)",
                    'severity' => 'warning',
                ];
            }
        }

        return $warnings;
    }

    public function checkBreakCompliance(int $userId, string $date): array
    {
        $warnings = [];
        $entries = $this->db->fetchAll(
            'SELECT clock_in, clock_out, break_minutes FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) = ? AND clock_out IS NOT NULL',
            [$userId, $date]
        );

        $totalWorkMinutes = 0;
        $totalBreakMinutes = 0;
        foreach ($entries as $entry) {
            $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
            $totalWorkMinutes += $diff / 60;
            $totalBreakMinutes += $entry['break_minutes'];
        }

        $totalWorkHours = $totalWorkMinutes / 60;
        if ($totalWorkHours > 6 && $totalBreakMinutes < 15) {
            $warnings[] = [
                'type' => 'break_not_taken',
                'message' => "Descanso obligatorio no registrado: trabajadas " . round($totalWorkHours, 1) . "h sin pausa mínima de 15 minutos",
                'severity' => 'warning',
            ];
        }

        return $warnings;
    }

    public function getComplianceAlerts(int $userId): array
    {
        $alerts = [];
        $today = date('Y-m-d');
        $mondayThisWeek = date('Y-m-d', strtotime('monday this week'));
        $year = (int) date('Y');

        $alerts = array_merge($alerts, $this->checkDailyHours($userId, $today));
        $alerts = array_merge($alerts, $this->checkWeeklyHours($userId, $mondayThisWeek));
        $alerts = array_merge($alerts, $this->checkAnnualOvertime($userId, $year));
        $alerts = array_merge($alerts, $this->checkRestBetweenDays($userId, $today));
        $alerts = array_merge($alerts, $this->checkBreakCompliance($userId, $today));

        return $alerts;
    }

    public function logAudit(int $timeEntryId, int $userId, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $this->db->insert('audit_log', [
            'time_entry_id' => $timeEntryId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $ipAddress,
        ]);
    }

    public function isRecordLocked(array $entry): bool
    {
        if (!empty($entry['is_locked'])) {
            return true;
        }

        if (!empty($entry['clock_in'])) {
            $hoursSinceEntry = (time() - strtotime($entry['clock_in'])) / 3600;
            return $hoursSinceEntry > 72;
        }

        return false;
    }

    public function getOvertimeHoursForYear(int $userId, int $year): float
    {
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        // Get daily totals
        $dailyTotals = $this->db->fetchAll(
            'SELECT DATE(clock_in) as work_date, 
                    SUM(TIMESTAMPDIFF(MINUTE, clock_in, clock_out) - break_minutes) as total_minutes
             FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) >= ? AND DATE(clock_in) <= ? AND clock_out IS NOT NULL
             GROUP BY DATE(clock_in)',
            [$userId, $startDate, $endDate]
        );

        $totalOvertime = 0;
        foreach ($dailyTotals as $day) {
            $hoursWorked = $day['total_minutes'] / 60;
            if ($hoursWorked > 8) {
                $totalOvertime += ($hoursWorked - 8);
            }
        }

        return round($totalOvertime, 2);
    }
}
