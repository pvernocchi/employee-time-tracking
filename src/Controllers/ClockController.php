<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\ComplianceService;

class ClockController
{
    private ComplianceService $compliance;

    public function __construct()
    {
        $this->compliance = new ComplianceService();
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();

        $activeEntry = $db->fetchOne(
            'SELECT * FROM time_entries WHERE user_id = ? AND status = "active" ORDER BY clock_in DESC LIMIT 1',
            [$userId]
        );

        // Today's entries
        $today = date('Y-m-d');
        $entries = $db->fetchAll(
            'SELECT * FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ? ORDER BY clock_in DESC',
            [$userId, $today]
        );

        // Get compliance warnings
        $complianceWarnings = $this->compliance->getComplianceAlerts($userId);

        View::render('dashboard.clock', [
            'activeEntry' => $activeEntry,
            'entries' => $entries,
            'complianceWarnings' => $complianceWarnings,
        ]);
    }

    public function clockIn(): void
    {
        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /clock');
            exit;
        }

        $db = Database::getInstance();
        $userId = Auth::id();

        // Check if already clocked in
        $active = $db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND status = "active"',
            [$userId]
        );

        if ($active) {
            $_SESSION['flash_error'] = 'You are already clocked in.';
            header('Location: /clock');
            exit;
        }

        // Check 12h rest period (warn but don't block)
        $restWarnings = $this->compliance->checkRestBetweenDays($userId, date('Y-m-d'));
        if (!empty($restWarnings)) {
            $_SESSION['flash_warning'] = $restWarnings[0]['message'];
        }

        $entryId = $db->insert('time_entries', [
            'user_id' => $userId,
            'clock_in' => date('Y-m-d H:i:s'),
            'status' => 'active',
        ]);

        // Audit log
        $this->compliance->logAudit($entryId, $userId, 'create', null, [
            'clock_in' => date('Y-m-d H:i:s'),
            'status' => 'active',
        ]);

        $_SESSION['flash_success'] = 'Clocked in successfully!';
        header('Location: /clock');
        exit;
    }

    public function clockOut(): void
    {
        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /clock');
            exit;
        }

        $db = Database::getInstance();
        $userId = Auth::id();

        $active = $db->fetchOne(
            'SELECT * FROM time_entries WHERE user_id = ? AND status = "active"',
            [$userId]
        );

        if (!$active) {
            $_SESSION['flash_error'] = 'You are not clocked in.';
            header('Location: /clock');
            exit;
        }

        $breakMinutes = (int) ($_POST['break_minutes'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $clockOut = date('Y-m-d H:i:s');

        $db->update('time_entries', [
            'clock_out' => $clockOut,
            'break_minutes' => $breakMinutes,
            'notes' => $notes,
            'status' => 'completed',
        ], 'id = ?', [$active['id']]);

        // Audit log
        $this->compliance->logAudit($active['id'], $userId, 'edit', 
            ['status' => 'active', 'clock_out' => null],
            ['status' => 'completed', 'clock_out' => $clockOut, 'break_minutes' => $breakMinutes]
        );

        // Check daily hours and break compliance
        $today = date('Y-m-d');
        $dailyWarnings = $this->compliance->checkDailyHours($userId, $today);
        $breakWarnings = $this->compliance->checkBreakCompliance($userId, $today);

        $warnings = array_merge($dailyWarnings, $breakWarnings);
        if (!empty($warnings)) {
            $messages = array_map(fn($w) => $w['message'], $warnings);
            $_SESSION['flash_warning'] = implode(' | ', $messages);
        }

        $_SESSION['flash_success'] = 'Clocked out successfully!';
        header('Location: /clock');
        exit;
    }
}
