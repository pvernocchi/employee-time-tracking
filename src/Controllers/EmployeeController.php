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
            'SELECT id, email, first_name, last_name, role, department, hourly_rate, is_active, created_at FROM users ORDER BY last_name, first_name'
        );

        View::render('employees.index', ['employees' => $employees]);
    }

    public function create(): void
    {
        View::render('employees.create');
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

        $validRoles = ['admin', 'manager', 'employee'];
        if (!in_array($role, $validRoles)) {
            $role = 'employee';
        }

        $db->insert('users', [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'department' => $department ?: null,
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
        ]);

        $_SESSION['flash_success'] = 'Employee created successfully.';
        header('Location: /admin/employees');
        exit;
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $employee = $db->fetchOne(
            'SELECT id, email, first_name, last_name, role, department, hourly_rate, is_active FROM users WHERE id = ?',
            [(int) $id]
        );

        if (!$employee) {
            $_SESSION['flash_error'] = 'Employee not found.';
            header('Location: /admin/employees');
            exit;
        }

        View::render('employees.edit', ['employee' => $employee]);
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
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Update password only if provided
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $validRoles = ['admin', 'manager', 'employee'];
        if (!in_array($data['role'], $validRoles)) {
            $data['role'] = 'employee';
        }

        $db->update('users', $data, 'id = ?', [(int) $id]);

        $_SESSION['flash_success'] = 'Employee updated successfully.';
        header('Location: /admin/employees');
        exit;
    }
}
