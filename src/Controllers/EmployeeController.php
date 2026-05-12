<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class EmployeeController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $employees = $db->fetchAll(
            'SELECT e.id, e.email, e.first_name, e.last_name, e.role, e.department, e.hourly_rate, e.manager_id, e.is_active, e.created_at,
                    m.first_name AS manager_first_name, m.last_name AS manager_last_name
             FROM users e
             LEFT JOIN users m ON e.manager_id = m.id
             ORDER BY e.last_name, e.first_name'
        );

        View::render('employees.index', ['employees' => $employees]);
    }

    public function create(): void
    {
        $db = Database::getInstance();
        $managers = $db->fetchAll(
            'SELECT id, first_name, last_name, role FROM users WHERE is_active = 1 AND role IN ("manager", "admin") ORDER BY last_name, first_name'
        );
        $teamCandidates = $db->fetchAll(
            'SELECT id, first_name, last_name, role FROM users WHERE is_active = 1 AND role IN ("employee", "manager") ORDER BY last_name, first_name'
        );

        View::render('employees.create', [
            'managers' => $managers,
            'teamCandidates' => $teamCandidates,
        ]);
    }

    public function store(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /admin/employees/create');
            exit;
        }

        $db = Database::getInstance();

        $email = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'employee';
        $department = trim($_POST['department'] ?? '');
        $hourlyRate = $_POST['hourly_rate'] ?? null;
        $managerId = isset($_POST['manager_id']) && $_POST['manager_id'] !== '' ? (int) $_POST['manager_id'] : null;
        $teamMemberIds = array_map('intval', $_POST['team_member_ids'] ?? []);

        // Validation
        if (empty($email) || empty($firstName) || empty($lastName) || empty($password)) {
            $_SESSION['flash_error'] = 'All required fields must be filled.';
            header('Location: /admin/employees/create');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Invalid email address.';
            header('Location: /admin/employees/create');
            exit;
        }

        // Check duplicate email
        $existing = $db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            $_SESSION['flash_error'] = 'Email already exists.';
            header('Location: /admin/employees/create');
            exit;
        }

        $validRoles = ['admin', 'manager', 'employee', 'inspector'];
        if (!in_array($role, $validRoles)) {
            $role = 'employee';
        }

        if ($role === 'employee' && $managerId === null) {
            $_SESSION['flash_error'] = 'Employees must have a manager assigned.';
            header('Location: /admin/employees/create');
            exit;
        }

        if ($role === 'employee' || $role === 'manager') {
            if ($managerId !== null) {
                $manager = $db->fetchOne(
                    'SELECT id FROM users WHERE id = ? AND is_active = 1 AND role IN ("manager", "admin")',
                    [$managerId]
                );
                if (!$manager) {
                    $_SESSION['flash_error'] = 'Selected manager is invalid.';
                    header('Location: /admin/employees/create');
                    exit;
                }
            }
        } else {
            $managerId = null;
        }

        $newUserId = $db->insert('users', [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'department' => $department ?: null,
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
            'manager_id' => $managerId,
        ]);

        if ($role === 'manager') {
            $this->syncTeamMembers($db, $newUserId, $teamMemberIds);
        }

        // Auto-create leave balances with default vacation days (Art. 38 ET)
        $currentYear = date('Y');
        $db->insert('leave_balances', [
            'user_id' => $newUserId,
            'leave_type' => 'vacation',
            'year' => $currentYear,
            'total_days' => 22,
            'used_days' => 0,
        ]);

        $_SESSION['flash_success'] = 'Employee created successfully.';
        header('Location: /admin/employees');
        exit;
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $employee = $db->fetchOne(
            'SELECT id, email, first_name, last_name, role, department, hourly_rate, manager_id, is_active FROM users WHERE id = ?',
            [(int) $id]
        );

        if (!$employee) {
            $_SESSION['flash_error'] = 'Employee not found.';
            header('Location: /admin/employees');
            exit;
        }

        $managers = $db->fetchAll(
            'SELECT id, first_name, last_name, role FROM users WHERE is_active = 1 AND role IN ("manager", "admin") AND id != ? ORDER BY last_name, first_name',
            [(int) $id]
        );
        $teamCandidates = $db->fetchAll(
            'SELECT id, first_name, last_name, role FROM users WHERE is_active = 1 AND role IN ("employee", "manager") AND id != ? ORDER BY last_name, first_name',
            [(int) $id]
        );
        $assignedTeamRows = $db->fetchAll(
            'SELECT id FROM users WHERE manager_id = ?',
            [(int) $id]
        );
        $assignedTeamIds = array_map(static fn(array $row): int => (int) $row['id'], $assignedTeamRows);

        View::render('employees.edit', [
            'employee' => $employee,
            'managers' => $managers,
            'teamCandidates' => $teamCandidates,
            'assignedTeamIds' => $assignedTeamIds,
        ]);
    }

    public function update(string $id): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header("Location: /admin/employees/edit/{$id}");
            exit;
        }

        $db = Database::getInstance();

        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'role' => $_POST['role'] ?? 'employee',
            'department' => trim($_POST['department'] ?? '') ?: null,
            'hourly_rate' => $_POST['hourly_rate'] ? (float) $_POST['hourly_rate'] : null,
            'manager_id' => isset($_POST['manager_id']) && $_POST['manager_id'] !== '' ? (int) $_POST['manager_id'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $teamMemberIds = array_map('intval', $_POST['team_member_ids'] ?? []);

        // Update password only if provided
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $validRoles = ['admin', 'manager', 'employee', 'inspector'];
        if (!in_array($data['role'], $validRoles)) {
            $data['role'] = 'employee';
        }

        $employeeId = (int) $id;

        if ($data['role'] === 'employee' && $data['manager_id'] === null) {
            $_SESSION['flash_error'] = 'Employees must have a manager assigned.';
            header("Location: /admin/employees/edit/{$id}");
            exit;
        }

        if ($data['manager_id'] !== null) {
            if ($data['manager_id'] === $employeeId) {
                $_SESSION['flash_error'] = 'An employee cannot be their own manager.';
                header("Location: /admin/employees/edit/{$id}");
                exit;
            }

            if (!in_array($data['role'], ['employee', 'manager'], true)) {
                $_SESSION['flash_error'] = 'Only employees and managers can have a manager assigned.';
                header("Location: /admin/employees/edit/{$id}");
                exit;
            }

            $manager = $db->fetchOne(
                'SELECT id FROM users WHERE id = ? AND is_active = 1 AND role IN ("manager", "admin")',
                [$data['manager_id']]
            );
            if (!$manager) {
                $_SESSION['flash_error'] = 'Selected manager is invalid.';
                header("Location: /admin/employees/edit/{$id}");
                exit;
            }
        }

        if (!in_array($data['role'], ['employee', 'manager'], true)) {
            $data['manager_id'] = null;
        }

        $db->update('users', $data, 'id = ?', [(int) $id]);

        if ($data['role'] === 'manager') {
            $this->syncTeamMembers($db, $employeeId, $teamMemberIds);
        } else {
            $db->query('UPDATE users SET manager_id = NULL WHERE manager_id = ?', [$employeeId]);
        }

        $_SESSION['flash_success'] = 'Employee updated successfully.';
        header('Location: /admin/employees');
        exit;
    }

    private function syncTeamMembers(Database $db, int $managerId, array $teamMemberIds): void
    {
        $db->query('UPDATE users SET manager_id = NULL WHERE manager_id = ?', [$managerId]);

        $validTeamMemberIds = array_filter(
            $teamMemberIds,
            static fn(int $memberId): bool => $memberId > 0 && $memberId !== $managerId
        );
        $teamMemberIds = array_values(array_unique($validTeamMemberIds));
        if ($teamMemberIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($teamMemberIds), '?'));
        $params = array_merge([$managerId], $teamMemberIds);

        $db->query(
            "UPDATE users SET manager_id = ? WHERE id IN ({$placeholders}) AND role IN ('employee', 'manager')",
            $params
        );
    }
}
