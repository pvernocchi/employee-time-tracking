<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class DashboardController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();
        $role = Auth::role();

        // Get current clock status
        $activeEntry = $db->fetchOne(
            'SELECT * FROM time_entries WHERE user_id = ? AND status = "active" ORDER BY clock_in DESC LIMIT 1',
            [$userId]
        );

        // Get today's total hours
        $today = date('Y-m-d');
        $todayEntries = $db->fetchAll(
            'SELECT * FROM time_entries WHERE user_id = ? AND DATE(clock_in) = ? ORDER BY clock_in',
            [$userId, $today]
        );

        $todayHours = 0;
        foreach ($todayEntries as $entry) {
            if ($entry['clock_out']) {
                $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
                $todayHours += ($diff / 3600) - ($entry['break_minutes'] / 60);
            } elseif ($entry['status'] === 'active') {
                $diff = time() - strtotime($entry['clock_in']);
                $todayHours += ($diff / 3600) - ($entry['break_minutes'] / 60);
            }
        }

        // Get this week's hours
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEntries = $db->fetchAll(
            'SELECT * FROM time_entries WHERE user_id = ? AND DATE(clock_in) >= ? AND clock_out IS NOT NULL',
            [$userId, $weekStart]
        );

        $weekHours = 0;
        foreach ($weekEntries as $entry) {
            $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
            $weekHours += ($diff / 3600) - ($entry['break_minutes'] / 60);
        }

        // Pending leave requests
        $pendingLeave = $db->fetchAll(
            'SELECT * FROM leave_requests WHERE user_id = ? AND status = "pending"',
            [$userId]
        );

        // Admin/Manager data
        $adminData = [];
        if (Auth::isManager()) {
            $adminData['pending_leave_count'] = $db->fetchOne(
                'SELECT COUNT(*) as count FROM leave_requests WHERE status = "pending"'
            )['count'];

            $adminData['active_employees'] = $db->fetchOne(
                'SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND role = "employee"'
            )['count'];

            $adminData['clocked_in_count'] = $db->fetchOne(
                'SELECT COUNT(DISTINCT user_id) as count FROM time_entries WHERE status = "active"'
            )['count'];
        }

        View::render('dashboard.index', [
            'activeEntry' => $activeEntry,
            'todayHours' => round($todayHours, 2),
            'weekHours' => round($weekHours, 2),
            'pendingLeave' => $pendingLeave,
            'adminData' => $adminData,
        ]);
    }
}
