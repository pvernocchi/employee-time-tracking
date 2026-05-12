<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

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

        View::render('timesheet.weekly', [
            'days' => $days,
            'weekOffset' => $weekOffset,
            'mondayDate' => $mondayDate,
            'sundayDate' => $sundayDate,
            'totalWeekHours' => round($totalWeekHours, 2),
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
}
