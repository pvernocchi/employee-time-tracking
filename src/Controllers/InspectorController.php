<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\View;

class InspectorController
{
    public function index(): void
    {
        $db = Database::getInstance();

        $employees = $db->fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.department,
                    COUNT(te.id) as total_entries,
                    MAX(te.clock_in) as last_clock_in
             FROM users u
             LEFT JOIN time_entries te ON u.id = te.user_id
             WHERE u.is_active = 1 AND u.role != "inspector"
             GROUP BY u.id
             ORDER BY u.last_name, u.first_name'
        );

        View::render('inspector.index', [
            'employees' => $employees,
        ]);
    }

    public function viewEmployee(string $id): void
    {
        $db = Database::getInstance();

        $employee = $db->fetchOne(
            'SELECT id, first_name, last_name, email, department, created_at FROM users WHERE id = ?',
            [(int) $id]
        );

        if (!$employee) {
            $_SESSION['flash_error'] = I18n::translate('inspector.employee_not_found');
            header('Location: /inspector');
            exit;
        }

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $entries = $db->fetchAll(
            'SELECT te.*, ed.first_name as editor_first, ed.last_name as editor_last
             FROM time_entries te
             LEFT JOIN users ed ON te.edited_by = ed.id
             WHERE te.user_id = ? AND DATE(te.clock_in) >= ? AND DATE(te.clock_in) <= ?
             ORDER BY te.clock_in DESC',
            [(int) $id, $startDate, $endDate]
        );

        $auditTrail = $db->fetchAll(
            'SELECT al.*, u.first_name, u.last_name
             FROM audit_log al
             JOIN users u ON al.user_id = u.id
             WHERE al.time_entry_id IN (SELECT id FROM time_entries WHERE user_id = ?)
             ORDER BY al.created_at DESC LIMIT 50',
            [(int) $id]
        );

        View::render('inspector.employee', [
            'employee' => $employee,
            'entries' => $entries,
            'auditTrail' => $auditTrail,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(): void
    {
        $db = Database::getInstance();

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $entries = $db->fetchAll(
            'SELECT te.*, u.first_name, u.last_name, u.email, u.department
             FROM time_entries te
             JOIN users u ON te.user_id = u.id
             WHERE DATE(te.clock_in) >= ? AND DATE(te.clock_in) <= ?
             ORDER BY u.last_name, u.first_name, te.clock_in',
            [$startDate, $endDate]
        );

        $filename = I18n::translate('inspector.csv.filename_prefix') . "_{$startDate}_{$endDate}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            I18n::translate('inspector.csv.employee'),
            I18n::translate('inspector.csv.email'),
            I18n::translate('inspector.csv.department'),
            I18n::translate('inspector.csv.date'),
            I18n::translate('inspector.csv.clock_in'),
            I18n::translate('inspector.csv.clock_out'),
            I18n::translate('inspector.csv.break_minutes'),
            I18n::translate('inspector.csv.hours_worked'),
            I18n::translate('inspector.csv.status'),
            I18n::translate('inspector.csv.notes'),
        ], ';');

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
                date('d/m/Y', strtotime($entry['clock_in'])),
                date('H:i', strtotime($entry['clock_in'])),
                $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : I18n::translate('inspector.csv.active'),
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
