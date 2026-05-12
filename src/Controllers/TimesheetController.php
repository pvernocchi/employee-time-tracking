<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\ComplianceService;

class TimesheetController
{
    public function index(): void
    {
        $this->weekly();
    }

    public function weekly(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();

        // Get week offset from query parameter
        $weekOffset = (int) ($_GET['week'] ?? 0);
        $mondayDate = date('Y-m-d', strtotime("monday this week {$weekOffset} weeks"));
        $sundayDate = date('Y-m-d', strtotime("{$mondayDate} +6 days"));

        $entries = $db->fetchAll(
            'SELECT * FROM time_entries WHERE user_id = ? AND DATE(clock_in) >= ? AND DATE(clock_in) <= ? ORDER BY clock_in',
            [$userId, $mondayDate, $sundayDate]
        );

        // Organize by day
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("{$mondayDate} +{$i} days"));
            $days[$date] = [
                'date' => $date,
                'dayName' => date('l', strtotime($date)),
                'entries' => [],
                'totalHours' => 0,
            ];
        }

        foreach ($entries as $entry) {
            $date = date('Y-m-d', strtotime($entry['clock_in']));
            if (isset($days[$date])) {
                $days[$date]['entries'][] = $entry;
                if ($entry['clock_out']) {
                    $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
                    $days[$date]['totalHours'] += ($diff / 3600) - ($entry['break_minutes'] / 60);
                }
            }
        }

        $totalWeekHours = array_sum(array_column($days, 'totalHours'));

        // Compliance: weekly hours warning
        $weeklyWarning = $totalWeekHours > 40;

        // Overtime calculation
        $compliance = new ComplianceService();
        $yearOvertime = $compliance->getOvertimeHoursForYear($userId, (int) date('Y'));

        View::render('timesheet.weekly', [
            'days' => $days,
            'weekOffset' => $weekOffset,
            'mondayDate' => $mondayDate,
            'sundayDate' => $sundayDate,
            'totalWeekHours' => round($totalWeekHours, 2),
            'weeklyWarning' => $weeklyWarning,
            'yearOvertime' => $yearOvertime,
        ]);
    }

    public function monthly(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();

        $monthOffset = (int) ($_GET['month'] ?? 0);
        $year = date('Y', strtotime("{$monthOffset} months"));
        $month = date('m', strtotime("{$monthOffset} months"));
        $firstDay = "{$year}-{$month}-01";
        $lastDay = date('Y-m-t', strtotime($firstDay));

        $entries = $db->fetchAll(
            'SELECT DATE(clock_in) as work_date, 
                    SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW())) - break_minutes) as total_minutes,
                    COUNT(*) as entry_count
             FROM time_entries 
             WHERE user_id = ? AND DATE(clock_in) >= ? AND DATE(clock_in) <= ?
             GROUP BY DATE(clock_in)
             ORDER BY work_date',
            [$userId, $firstDay, $lastDay]
        );

        $totalMonthHours = 0;
        $dailyData = [];
        foreach ($entries as $entry) {
            $hours = round($entry['total_minutes'] / 60, 2);
            $dailyData[$entry['work_date']] = [
                'hours' => $hours,
                'entries' => $entry['entry_count'],
            ];
            $totalMonthHours += $hours;
        }

        View::render('timesheet.monthly', [
            'dailyData' => $dailyData,
            'monthOffset' => $monthOffset,
            'year' => $year,
            'month' => $month,
            'monthName' => date('F Y', strtotime($firstDay)),
            'firstDay' => $firstDay,
            'lastDay' => $lastDay,
            'totalMonthHours' => round($totalMonthHours, 2),
        ]);
    }

    public function exportOwn(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();
        $user = Auth::user();

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $entries = $db->fetchAll(
            'SELECT * FROM time_entries WHERE user_id = ? AND DATE(clock_in) >= ? AND DATE(clock_in) <= ? ORDER BY clock_in',
            [$userId, $startDate, $endDate]
        );

        $filename = "mi_registro_jornada_{$startDate}_a_{$endDate}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'Fecha', 'Hora Entrada', 'Hora Salida', 'Pausa (min)',
            'Horas Trabajadas', 'Estado', 'Notas'
        ], ';');

        foreach ($entries as $entry) {
            $hours = 0;
            if ($entry['clock_out']) {
                $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
                $hours = round(($diff / 3600) - ($entry['break_minutes'] / 60), 2);
            }

            fputcsv($output, [
                date('d/m/Y', strtotime($entry['clock_in'])),
                date('H:i', strtotime($entry['clock_in'])),
                $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : 'Activo',
                $entry['break_minutes'],
                $hours,
                ucfirst($entry['status']),
                $entry['notes'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }
}
