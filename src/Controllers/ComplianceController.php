<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\I18n;
use App\Core\Database;
use App\Core\View;
use App\Core\ComplianceService;

class ComplianceController
{
    private ComplianceService $compliance;

    public function __construct()
    {
        $this->compliance = new ComplianceService();
    }

    public function dashboard(): void
    {
        $db = Database::getInstance();

        // Get all active employees and their alerts
        $employees = $db->fetchAll(
            'SELECT id, first_name, last_name, department FROM users WHERE is_active = 1 AND role != "inspector" ORDER BY last_name'
        );

        $allAlerts = [];
        foreach ($employees as $employee) {
            $alerts = $this->compliance->getComplianceAlerts($employee['id']);
            if (!empty($alerts)) {
                $allAlerts[] = [
                    'employee' => $employee,
                    'alerts' => $alerts,
                ];
            }
        }

        // Recent audit log entries
        $recentAudit = $db->fetchAll(
            'SELECT al.*, u.first_name, u.last_name, te.clock_in, te.clock_out
             FROM audit_log al
             JOIN users u ON al.user_id = u.id
             JOIN time_entries te ON al.time_entry_id = te.id
             ORDER BY al.created_at DESC LIMIT 20'
        );

        View::render('compliance.dashboard', [
            'allAlerts' => $allAlerts,
            'recentAudit' => $recentAudit,
            'totalEmployees' => count($employees),
            'violationCount' => count($allAlerts),
        ]);
    }

    public function auditLog(): void
    {
        $db = Database::getInstance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $entries = $db->fetchAll(
            'SELECT al.*, u.first_name, u.last_name, u.email,
                    te.clock_in, te.clock_out, eu.first_name as entry_user_first, eu.last_name as entry_user_last
             FROM audit_log al
             JOIN users u ON al.user_id = u.id
             JOIN time_entries te ON al.time_entry_id = te.id
             JOIN users eu ON te.user_id = eu.id
             ORDER BY al.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        $totalCount = $db->fetchOne('SELECT COUNT(*) as cnt FROM audit_log');

        View::render('compliance.audit_log', [
            'entries' => $entries,
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount['cnt'] ?? 0,
        ]);
    }

    public function exportForInspection(): void
    {
        $db = Database::getInstance();

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $entries = $db->fetchAll(
            'SELECT te.*, u.first_name, u.last_name, u.email, u.department,
                    ed.first_name as editor_first, ed.last_name as editor_last
             FROM time_entries te
             JOIN users u ON te.user_id = u.id
             LEFT JOIN users ed ON te.edited_by = ed.id
             WHERE DATE(te.clock_in) >= ? AND DATE(te.clock_in) <= ?
             ORDER BY u.last_name, u.first_name, te.clock_in',
            [$startDate, $endDate]
        );

        // Get audit trail for the period
        $auditTrail = $db->fetchAll(
            'SELECT al.*, u.first_name, u.last_name
             FROM audit_log al
             JOIN users u ON al.user_id = u.id
             JOIN time_entries te ON al.time_entry_id = te.id
             WHERE DATE(te.clock_in) >= ? AND DATE(te.clock_in) <= ?
             ORDER BY al.created_at',
            [$startDate, $endDate]
        );

        $filename = I18n::translate('compliance.export.filename_prefix') . "_{$startDate}_{$endDate}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        // BOM for UTF-8 in Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            I18n::translate('compliance.export.employee'),
            I18n::translate('compliance.export.email'),
            I18n::translate('compliance.export.department'),
            I18n::translate('compliance.export.date'),
            I18n::translate('compliance.export.clock_in'),
            I18n::translate('compliance.export.clock_out'),
            I18n::translate('compliance.export.break_minutes'),
            I18n::translate('compliance.export.hours_worked'),
            I18n::translate('compliance.export.status'),
            I18n::translate('compliance.export.locked'),
            I18n::translate('compliance.export.edited_by'),
            I18n::translate('compliance.export.edit_reason'),
            I18n::translate('compliance.export.notes'),
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
                $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : I18n::translate('compliance.export.active'),
                $entry['break_minutes'],
                $hours,
                ucfirst($entry['status']),
                $entry['is_locked'] ? I18n::translate('leave_policy.yes') : I18n::translate('leave_policy.no'),
                $entry['editor_first'] ? $entry['editor_first'] . ' ' . $entry['editor_last'] : '',
                $entry['edit_reason'] ?? '',
                $entry['notes'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function privacyNotice(): void
    {
        View::render('compliance.privacy_notice', []);
    }

    public function dataProtectionConsent(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = I18n::translate('flash.invalid_request_try_again');
            header('Location: /compliance/privacy');
            exit;
        }

        $db = Database::getInstance();
        $userId = Auth::id();
        $consentType = trim($_POST['consent_type'] ?? 'time_tracking_privacy_notice');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $db->insert('data_protection_consents', [
            'user_id' => $userId,
            'consent_type' => $consentType,
            'consented_at' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
        ]);

        $_SESSION['flash_success'] = I18n::translate('flash.compliance_consent_saved');
        header('Location: /compliance/privacy');
        exit;
    }
}
