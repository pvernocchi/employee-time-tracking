<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class LeaveController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();

        $requests = $db->fetchAll(
            'SELECT lr.*, u.first_name as reviewer_first, u.last_name as reviewer_last 
             FROM leave_requests lr 
             LEFT JOIN users u ON lr.reviewed_by = u.id 
             WHERE lr.user_id = ? 
             ORDER BY lr.created_at DESC',
            [$userId]
        );

        $balances = $db->fetchAll(
            'SELECT * FROM leave_balances WHERE user_id = ? AND year = ?',
            [$userId, date('Y')]
        );

        View::render('leave.index', [
            'requests' => $requests,
            'balances' => $balances,
        ]);
    }

    public function showRequest(): void
    {
        View::render('leave.request');
    }

    public function submitRequest(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /leave/request');
            exit;
        }

        $db = Database::getInstance();
        $userId = Auth::id();

        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        // Validation
        $validTypes = ['vacation', 'sick', 'personal', 'unpaid', 'other'];
        if (!in_array($leaveType, $validTypes)) {
            $_SESSION['flash_error'] = 'Invalid leave type.';
            header('Location: /leave/request');
            exit;
        }

        if (empty($startDate) || empty($endDate) || strtotime($endDate) < strtotime($startDate)) {
            $_SESSION['flash_error'] = 'Invalid date range.';
            header('Location: /leave/request');
            exit;
        }

        $db->insert('leave_requests', [
            'user_id' => $userId,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $_SESSION['flash_success'] = 'Leave request submitted successfully.';
        header('Location: /leave');
        exit;
    }

    public function cancel(string $id): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /leave');
            exit;
        }

        $db = Database::getInstance();
        $userId = Auth::id();

        $request = $db->fetchOne(
            'SELECT * FROM leave_requests WHERE id = ? AND user_id = ? AND status = "pending"',
            [(int) $id, $userId]
        );

        if (!$request) {
            $_SESSION['flash_error'] = 'Leave request not found or cannot be cancelled.';
            header('Location: /leave');
            exit;
        }

        $db->update('leave_requests', ['status' => 'cancelled'], 'id = ?', [(int) $id]);

        $_SESSION['flash_success'] = 'Leave request cancelled.';
        header('Location: /leave');
        exit;
    }

    public function adminIndex(): void
    {
        $db = Database::getInstance();

        $status = $_GET['status'] ?? 'pending';
        $validStatuses = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($status, $validStatuses)) {
            $status = 'pending';
        }

        $sql = 'SELECT lr.*, u.first_name, u.last_name, u.email, u.department 
                FROM leave_requests lr 
                JOIN users u ON lr.user_id = u.id';
        $params = [];

        if ($status !== 'all') {
            $sql .= ' WHERE lr.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY lr.created_at DESC';

        $requests = $db->fetchAll($sql, $params);

        View::render('leave.admin', [
            'requests' => $requests,
            'currentStatus' => $status,
        ]);
    }

    public function approve(string $id): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/leave');
            exit;
        }

        $db = Database::getInstance();

        $db->update('leave_requests', [
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => trim($_POST['notes'] ?? ''),
        ], 'id = ? AND status = "pending"', [(int) $id]);

        $_SESSION['flash_success'] = 'Leave request approved.';
        header('Location: /admin/leave');
        exit;
    }

    public function reject(string $id): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/leave');
            exit;
        }

        $db = Database::getInstance();

        $db->update('leave_requests', [
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => trim($_POST['notes'] ?? ''),
        ], 'id = ? AND status = "pending"', [(int) $id]);

        $_SESSION['flash_success'] = 'Leave request rejected.';
        header('Location: /admin/leave');
        exit;
    }
}
