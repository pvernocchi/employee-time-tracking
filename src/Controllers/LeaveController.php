<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\NotificationService;
use App\Core\View;

class LeaveController
{
    private const VALID_TYPES = ['vacation', 'sick', 'personal', 'unpaid', 'maternity', 'paternity', 'marriage', 'bereavement', 'moving', 'jury_duty', 'other'];
    private const MANAGEABLE_STATUSES = ['pending', 'approved'];
    private const DECEMBER_SPECIAL_DATES = ['12-24', '12-31'];

    public function index(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();
        $year = (int) date('Y');
        $vacationDecemberDeduction = $this->getVacationDecemberDeduction($db);

        $requests = $db->fetchAll(
            'SELECT lr.*, u.first_name as reviewer_first, u.last_name as reviewer_last 
             FROM leave_requests lr 
             LEFT JOIN users u ON lr.reviewed_by = u.id 
             WHERE lr.user_id = ? 
             ORDER BY lr.created_at DESC',
            [$userId]
        );
        $this->appendCalculatedDays($requests, $vacationDecemberDeduction);

        $balances = $db->fetchAll(
            'SELECT * FROM leave_balances WHERE user_id = ? AND year = ?',
            [$userId, $year]
        );

        $categoryTracking = $this->buildCategoryTracking($db, $userId, $year, $vacationDecemberDeduction);
        $teamAbsences = $this->getTeamAbsences($db, $userId, Auth::isManager());
        $this->appendCalculatedDays($teamAbsences, $vacationDecemberDeduction);

        View::render('leave.index', [
            'requests' => $requests,
            'balances' => $balances,
            'categoryTracking' => $categoryTracking,
            'teamAbsences' => $teamAbsences,
        ]);
    }

    public function showRequest(): void
    {
        View::render('leave.request', [
            'request' => [
                'leave_type' => '',
                'start_date' => '',
                'end_date' => '',
                'reason' => '',
                'status' => 'pending',
            ],
            'formAction' => '/leave/request',
            'pageTitle' => I18n::translate('leave.request.page_title'),
            'submitLabel' => I18n::translate('leave.request.submit'),
        ]);
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
        if (!in_array($leaveType, self::VALID_TYPES, true)) {
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

        // Notify manager(s) by email
        try {
            $employee = $db->fetchOne('SELECT id, first_name, last_name, email FROM users WHERE id = ?', [$userId]);
            if ($employee) {
                $notification = new NotificationService();
                $notification->notifyManagerLeaveRequest([
                    'leave_type' => $leaveType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => $reason,
                ], $employee);
            }
        } catch (\Throwable $e) {
            // Notification failure must not prevent the leave request from succeeding.
        }

        $_SESSION['flash_success'] = 'Leave request submitted successfully.';
        header('Location: /leave');
        exit;
    }

    public function showEdit(string $id): void
    {
        $request = $this->findManageableRequest((int) $id);

        if (!$request) {
            $_SESSION['flash_error'] = 'Leave request not found or cannot be modified.';
            header('Location: /leave');
            exit;
        }

        View::render('leave.request', [
            'request' => $request,
            'formAction' => "/leave/edit/{$request['id']}",
            'pageTitle' => I18n::translate('leave.request.edit_page_title'),
            'submitLabel' => $request['status'] === 'approved'
                ? I18n::translate('leave.request.update_and_resubmit')
                : I18n::translate('leave.request.update'),
        ]);
    }

    public function update(string $id): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header("Location: /leave/edit/{$id}");
            exit;
        }

        $db = Database::getInstance();
        $request = $this->findManageableRequest((int) $id);

        if (!$request) {
            $_SESSION['flash_error'] = 'Leave request not found or cannot be modified.';
            header('Location: /leave');
            exit;
        }

        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!in_array($leaveType, self::VALID_TYPES, true)) {
            $_SESSION['flash_error'] = 'Invalid leave type.';
            header("Location: /leave/edit/{$id}");
            exit;
        }

        if (empty($startDate) || empty($endDate) || strtotime($endDate) < strtotime($startDate)) {
            $_SESSION['flash_error'] = 'Invalid date range.';
            header("Location: /leave/edit/{$id}");
            exit;
        }

        $data = [
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
        ];

        if ($request['status'] === 'approved') {
            $data['status'] = 'pending';
            $data['reviewed_by'] = null;
            $data['reviewed_at'] = null;
            $data['review_notes'] = null;
        }

        $db->update('leave_requests', $data, 'id = ? AND user_id = ?', [(int) $id, Auth::id()]);

        // Notify manager(s) when a request is resubmitted for review
        if ($request['status'] === 'approved') {
            try {
                $employee = $db->fetchOne('SELECT id, first_name, last_name, email FROM users WHERE id = ?', [Auth::id()]);
                if ($employee) {
                    $notification = new NotificationService();
                    $notification->notifyManagerLeaveRequest([
                        'leave_type' => $leaveType,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'reason' => $reason,
                    ], $employee);
                }
            } catch (\Throwable $e) {
                // Notification failure must not break the update flow.
            }
        }

        $_SESSION['flash_success'] = $request['status'] === 'approved'
            ? 'Leave request updated and sent for review again.'
            : 'Leave request updated.';
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
        $request = $this->findManageableRequest((int) $id);

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

    private function findManageableRequest(int $id): ?array
    {
        $db = Database::getInstance();
        $placeholders = implode(', ', array_fill(0, count(self::MANAGEABLE_STATUSES), '?'));
        $today = $this->today();

        return $db->fetchOne(
            "SELECT * FROM leave_requests WHERE id = ? AND user_id = ? AND status IN ({$placeholders}) AND end_date >= ?",
            array_merge([$id, Auth::id()], self::MANAGEABLE_STATUSES, [$today])
        );
    }

    private function today(): string
    {
        static $today = null;

        if ($today === null) {
            $today = date('Y-m-d');
        }

        return $today;
    }

    public function adminIndex(): void
    {
        $db = Database::getInstance();
        $reviewerId = Auth::id();

        $status = $_GET['status'] ?? 'pending';
        $validStatuses = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($status, $validStatuses)) {
            $status = 'pending';
        }

        $sql = 'SELECT lr.*, u.first_name, u.last_name, u.email, u.department 
                FROM leave_requests lr 
                JOIN users u ON lr.user_id = u.id
                LEFT JOIN users um ON u.manager_id = um.id
                WHERE (u.manager_id = ? OR um.manager_id = ?)';
        $params = [$reviewerId, $reviewerId];

        if ($status !== 'all') {
            $sql .= ' AND lr.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY lr.created_at DESC';

        $requests = $db->fetchAll($sql, $params);
        $this->appendCalculatedDays($requests, $this->getVacationDecemberDeduction($db));

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
        $reviewerId = Auth::id();

        $request = $db->fetchOne(
            'SELECT lr.id
             FROM leave_requests lr
             JOIN users u ON lr.user_id = u.id
             LEFT JOIN users um ON u.manager_id = um.id
             WHERE lr.id = ? AND lr.status = "pending" AND (u.manager_id = ? OR um.manager_id = ?)',
            [(int) $id, $reviewerId, $reviewerId]
        );

        if (!$request) {
            $_SESSION['flash_error'] = 'You are not allowed to approve this leave request.';
            header('Location: /admin/leave');
            exit;
        }

        $db->update('leave_requests', [
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
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
        $reviewerId = Auth::id();

        $request = $db->fetchOne(
            'SELECT lr.id
             FROM leave_requests lr
             JOIN users u ON lr.user_id = u.id
             LEFT JOIN users um ON u.manager_id = um.id
             WHERE lr.id = ? AND lr.status = "pending" AND (u.manager_id = ? OR um.manager_id = ?)',
            [(int) $id, $reviewerId, $reviewerId]
        );

        if (!$request) {
            $_SESSION['flash_error'] = 'You are not allowed to reject this leave request.';
            header('Location: /admin/leave');
            exit;
        }

        $db->update('leave_requests', [
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => trim($_POST['notes'] ?? ''),
        ], 'id = ? AND status = "pending"', [(int) $id]);

        $_SESSION['flash_success'] = 'Leave request rejected.';
        header('Location: /admin/leave');
        exit;
    }

    private function appendCalculatedDays(array &$requests, float $vacationDecemberDeduction): void
    {
        foreach ($requests as &$request) {
            $request['calculated_days'] = $this->calculateRequestDays(
                (string) ($request['leave_type'] ?? ''),
                (string) ($request['start_date'] ?? ''),
                (string) ($request['end_date'] ?? ''),
                $vacationDecemberDeduction
            );
        }
        unset($request);
    }

    private function buildCategoryTracking(Database $db, int $userId, int $year, float $vacationDecemberDeduction): array
    {
        try {
            $policies = $db->fetchAll(
                'SELECT category_key, name, legal_days, tracks_balance FROM leave_policy WHERE is_active = 1 ORDER BY is_statutory DESC, name ASC'
            );
        } catch (\Throwable $e) {
            return [];
        }

        if ($policies === []) {
            return [];
        }

        $yearStart = sprintf('%d-01-01', $year);
        $yearEnd = sprintf('%d-12-31', $year);
        $approvedRequests = $db->fetchAll(
            'SELECT leave_type, start_date, end_date
             FROM leave_requests
             WHERE user_id = ? AND status = "approved" AND start_date <= ? AND end_date >= ?',
            [$userId, $yearEnd, $yearStart]
        );

        $usedByCategory = [];
        foreach ($approvedRequests as $request) {
            $effectiveStart = max((string) $request['start_date'], $yearStart);
            $effectiveEnd = min((string) $request['end_date'], $yearEnd);
            $leaveType = (string) $request['leave_type'];
            $usedByCategory[$leaveType] = ($usedByCategory[$leaveType] ?? 0.0) + $this->calculateRequestDays(
                $leaveType,
                $effectiveStart,
                $effectiveEnd,
                $vacationDecemberDeduction
            );
        }

        $tracking = [];
        foreach ($policies as $policy) {
            $categoryKey = (string) $policy['category_key'];
            $tracksBalance = (bool) ($policy['tracks_balance'] ?? true);
            $totalDays = (float) $policy['legal_days'];
            $usedDays = round((float) ($usedByCategory[$categoryKey] ?? 0.0), 1);
            $tracking[] = [
                'category_key' => $categoryKey,
                'name' => (string) $policy['name'],
                'total_days' => $totalDays,
                'used_days' => $usedDays,
                'available_days' => $tracksBalance ? round($totalDays - $usedDays, 1) : null,
                'tracks_balance' => $tracksBalance,
            ];
        }

        return $tracking;
    }

    private function getVacationDecemberDeduction(Database $db): float
    {
        try {
            $policy = $db->fetchOne(
                'SELECT dec_24_31_deduction FROM leave_policy WHERE category_key = "vacation" LIMIT 1'
            );
            return (($policy['dec_24_31_deduction'] ?? 'full') === 'half') ? 0.5 : 1.0;
        } catch (\Throwable $e) {
            return 1.0;
        }
    }

    private function calculateRequestDays(string $leaveType, string $startDate, string $endDate, float $vacationDecemberDeduction): float
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if (!$start || !$end || $end < $start) {
            return 0.0;
        }

        if ($leaveType !== 'vacation') {
            return (float) ((int) $start->diff($end)->format('%a') + 1);
        }

        $days = 0.0;
        $oneDayInterval = new \DateInterval('P1D');
        for ($current = $start; $current <= $end; $current = $current->add($oneDayInterval)) {
            $weekDay = (int) $current->format('N');
            if ($weekDay >= 6) {
                continue;
            }

            $monthDay = $current->format('m-d');
            if (in_array($monthDay, self::DECEMBER_SPECIAL_DATES, true)) {
                $days += $vacationDecemberDeduction;
                continue;
            }

            $days += 1.0;
        }

        return round($days, 1);
    }

    private function getTeamAbsences(Database $db, int $userId, bool $canViewManagedEmployees): array
    {
        $currentUser = $db->fetchOne('SELECT manager_id FROM users WHERE id = ?', [$userId]);
        if (!$currentUser) {
            return [];
        }

        $visibleManagerIds = [];

        if (!empty($currentUser['manager_id'])) {
            $visibleManagerIds[] = (int) $currentUser['manager_id'];
        }

        if ($canViewManagedEmployees) {
            $visibleManagerIds[] = $userId;
        }

        $visibleManagerIds = array_values(array_unique($visibleManagerIds));
        $visibleManagerCount = count($visibleManagerIds);
        if ($visibleManagerCount === 0) {
            return [];
        }

        $managerIdPlaceholders = implode(', ', array_fill(0, $visibleManagerCount, '?'));
        $params = array_merge([$this->today(), $userId], $visibleManagerIds);

        $sql = 'SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.status, u.first_name, u.last_name
                FROM leave_requests lr
                JOIN users u ON lr.user_id = u.id
                WHERE lr.status = "approved"
                  AND lr.end_date >= ?
                  AND u.id <> ?
                  AND u.is_active = 1
                  AND u.manager_id IN (' . $managerIdPlaceholders . ')
                ORDER BY lr.start_date ASC, u.last_name ASC, u.first_name ASC';

        return $db->fetchAll($sql, $params);
    }
}
