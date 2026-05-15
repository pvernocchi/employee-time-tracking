<?php

namespace App\Controllers;

use App\Core\ApiAuth;
use App\Core\Database;

class ApiController
{
    private const VALID_LEAVE_TYPES = ['vacation', 'sick', 'personal', 'unpaid', 'maternity', 'paternity', 'marriage', 'bereavement', 'moving', 'jury_duty', 'other'];

    public function me(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $this->json(['data' => $user]);
    }

    public function activeEntry(): void
    {
        $db = Database::getInstance();
        $entry = $db->fetchOne(
            'SELECT id, user_id, clock_in, clock_out, break_minutes, notes, status, created_at, updated_at
             FROM time_entries
             WHERE user_id = ? AND status = "active"
             ORDER BY clock_in DESC
             LIMIT 1',
            [ApiAuth::userId()]
        );

        $this->json(['data' => $entry]);
    }

    public function listTimeEntries(): void
    {
        $startDate = $this->parseDate((string) ($_GET['start_date'] ?? date('Y-m-01')));
        $endDate = $this->parseDate((string) ($_GET['end_date'] ?? date('Y-m-d')));

        if ($startDate === null || $endDate === null || $endDate < $startDate) {
            $this->json(['error' => 'Invalid date range. Use YYYY-MM-DD.'], 422);
            return;
        }

        $db = Database::getInstance();
        $entries = $db->fetchAll(
            'SELECT id, user_id, clock_in, clock_out, break_minutes, notes, status, created_at, updated_at
             FROM time_entries
             WHERE user_id = ?
               AND DATE(clock_in) >= ?
               AND DATE(clock_in) <= ?
             ORDER BY clock_in DESC',
            [ApiAuth::userId(), $startDate, $endDate]
        );

        $this->json(['data' => $entries]);
    }

    public function clockIn(): void
    {
        $db = Database::getInstance();
        $userId = ApiAuth::userId();

        $active = $db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND status = "active" LIMIT 1',
            [$userId]
        );

        if ($active) {
            $this->json(['error' => 'You are already clocked in.'], 409);
            return;
        }

        $entryId = $db->insert('time_entries', [
            'user_id' => $userId,
            'clock_in' => date('Y-m-d H:i:s'),
            'status' => 'active',
        ]);

        $entry = $db->fetchOne(
            'SELECT id, user_id, clock_in, clock_out, break_minutes, notes, status, created_at, updated_at
             FROM time_entries
             WHERE id = ?',
            [$entryId]
        );

        $this->json(['data' => $entry], 201);
    }

    public function clockOut(): void
    {
        $db = Database::getInstance();
        $userId = ApiAuth::userId();
        $payload = $this->requestData();

        $active = $db->fetchOne(
            'SELECT id FROM time_entries WHERE user_id = ? AND status = "active" LIMIT 1',
            [$userId]
        );

        if (!$active) {
            $this->json(['error' => 'No active time entry found.'], 409);
            return;
        }

        $breakMinutes = (int) ($payload['break_minutes'] ?? 0);
        if ($breakMinutes < 0 || $breakMinutes > 720) {
            $this->json(['error' => 'break_minutes must be between 0 and 720.'], 422);
            return;
        }

        $notes = trim((string) ($payload['notes'] ?? ''));
        $clockOut = date('Y-m-d H:i:s');

        $db->update('time_entries', [
            'clock_out' => $clockOut,
            'break_minutes' => $breakMinutes,
            'notes' => $notes,
            'status' => 'completed',
        ], 'id = ?', [(int) $active['id']]);

        $entry = $db->fetchOne(
            'SELECT id, user_id, clock_in, clock_out, break_minutes, notes, status, created_at, updated_at
             FROM time_entries
             WHERE id = ?',
            [(int) $active['id']]
        );

        $this->json(['data' => $entry]);
    }

    public function listLeaveRequests(): void
    {
        $db = Database::getInstance();

        $requests = $db->fetchAll(
            'SELECT id, user_id, leave_type, start_date, end_date, reason, status, reviewed_by, reviewed_at, review_notes, created_at, updated_at
             FROM leave_requests
             WHERE user_id = ?
             ORDER BY created_at DESC',
            [ApiAuth::userId()]
        );

        $this->json(['data' => $requests]);
    }

    public function createLeaveRequest(): void
    {
        $payload = $this->requestData();
        $leaveType = (string) ($payload['leave_type'] ?? '');
        $startDate = $this->parseDate((string) ($payload['start_date'] ?? ''));
        $endDate = $this->parseDate((string) ($payload['end_date'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if (!in_array($leaveType, self::VALID_LEAVE_TYPES, true)) {
            $this->json(['error' => 'Invalid leave_type.'], 422);
            return;
        }

        if ($startDate === null || $endDate === null || $endDate < $startDate) {
            $this->json(['error' => 'Invalid date range. Use YYYY-MM-DD.'], 422);
            return;
        }

        $db = Database::getInstance();
        $requestId = $db->insert('leave_requests', [
            'user_id' => ApiAuth::userId(),
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $request = $db->fetchOne(
            'SELECT id, user_id, leave_type, start_date, end_date, reason, status, reviewed_by, reviewed_at, review_notes, created_at, updated_at
             FROM leave_requests
             WHERE id = ?',
            [$requestId]
        );

        $this->json(['data' => $request], 201);
    }

    private function requestData(): array
    {
        $contentType = strtolower(trim((string) ($_SERVER['CONTENT_TYPE'] ?? '')));
        if (str_starts_with($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody === false || trim($rawBody) === '') {
                return [];
            }

            $decoded = json_decode($rawBody, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    private function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
