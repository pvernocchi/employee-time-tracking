<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class ClockController
{
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

        View::render('dashboard.clock', [
            'activeEntry' => $activeEntry,
            'entries' => $entries,
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

        $db->insert('time_entries', [
            'user_id' => $userId,
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
            'SELECT id FROM time_entries WHERE user_id = ? AND status = "active"',
            [$userId]
        );

        if (!$active) {
            $_SESSION['flash_error'] = 'You are not clocked in.';
            header('Location: /clock');
            exit;
        }

        $breakMinutes = (int) ($_POST['break_minutes'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        $db->update('time_entries', [
            'clock_out' => date('Y-m-d H:i:s'),
            'break_minutes' => $breakMinutes,
            'notes' => $notes,
            'status' => 'completed',
        ], 'id = ?', [$active['id']]);

        $_SESSION['flash_success'] = 'Clocked out successfully!';
        header('Location: /clock');
        exit;
    }
}
