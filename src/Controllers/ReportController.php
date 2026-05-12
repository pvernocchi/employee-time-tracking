<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\ComplianceService;

class ReportController
{
    public function index(): void
    {
        $db = Database::getInstance();

        $employees = $db->fetchAll(
            'SELECT id, first_name, last_name, department FROM users WHERE is_active = 1 ORDER BY last_name'
        );

        View::render('reports.index', ['employees' => $employees]);
    }

    public function export(): void
    {
        $db = Database::getInstance();

        $employeeId = $_GET['employee_id'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $format = $_GET['format'] ?? 'csv';

        $sql = 'SELECT te.*, u.first_name, u.last_name, u.email, u.department
                FROM time_entries te
                JOIN users u ON te.user_id = u.id
                WHERE DATE(te.clock_in) >= ? AND DATE(te.clock_in) <= ?';
        $params = [$startDate, $endDate];

        if (!empty($employeeId)) {
            $sql .= ' AND te.user_id = ?';
            $params[] = (int) $employeeId;
        }
        $sql .= ' ORDER BY te.clock_in';

        $entries = $db->fetchAll($sql, $params);

        if ($format === 'csv') {
            $this->exportCsv($entries, $startDate, $endDate);
        } else {
            // Default to showing results in browser
            View::render('reports.results', [
                'entries' => $entries,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        }
    }

    public function overtime(): void
    {
        $db = Database::getInstance();
        $compliance = new ComplianceService();

        $year = (int) ($_GET['year'] ?? date('Y'));

        $employees = $db->fetchAll(
            'SELECT id, first_name, last_name, department FROM users WHERE is_active = 1 AND role != "inspector" ORDER BY last_name'
        );

        $overtimeData = [];
        foreach ($employees as $employee) {
            $hours = $compliance->getOvertimeHoursForYear($employee['id'], $year);
            $overtimeData[] = [
                'employee' => $employee,
                'overtime_hours' => $hours,
                'limit_exceeded' => $hours > 80,
            ];
        }

        View::render('reports.overtime', [
            'overtimeData' => $overtimeData,
            'year' => $year,
        ]);
    }

    public function violations(): void
    {
        $db = Database::getInstance();
        $compliance = new ComplianceService();

        $employees = $db->fetchAll(
            'SELECT id, first_name, last_name, department FROM users WHERE is_active = 1 AND role != "inspector" ORDER BY last_name'
        );

        $allViolations = [];
        foreach ($employees as $employee) {
            $alerts = $compliance->getComplianceAlerts($employee['id']);
            if (!empty($alerts)) {
                $allViolations[] = [
                    'employee' => $employee,
                    'violations' => $alerts,
                ];
            }
        }

        View::render('reports.violations', [
            'allViolations' => $allViolations,
        ]);
    }

    private function exportCsv(array $entries, string $startDate, string $endDate): void
    {
        $filename = "timesheet_report_{$startDate}_to_{$endDate}.csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, [
            'Employee',
            'Email',
            'Department',
            'Date',
            'Clock In',
            'Clock Out',
            'Break (min)',
            'Hours Worked',
            'Notes',
            'Status',
        ]);

        foreach ($entries as $entry) {
            $hours = 0;
            if ($entry['clock_out']) {
                $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
                $hours = round(($diff / 3600) - ($entry['break_minutes'] / 60), 2);
            }

            fputcsv($output, [
                $entry['first_name'] . ' ' . $entry['last_name'],
                $entry['email'],
                $entry['department'] ?? '',
                date('Y-m-d', strtotime($entry['clock_in'])),
                date('H:i', strtotime($entry['clock_in'])),
                $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : 'Active',
                $entry['break_minutes'],
                $hours,
                $entry['notes'] ?? '',
                $entry['status'],
            ]);
        }

        fclose($output);
        exit;
    }
}
