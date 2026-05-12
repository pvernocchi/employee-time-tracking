<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class LeavePolicyController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $policies = $db->fetchAll(
            'SELECT * FROM leave_policy ORDER BY is_statutory DESC, name ASC'
        );

        View::render('admin.settings.leave_policy', ['policies' => $policies]);
    }

    public function create(): void
    {
        View::render('admin.settings.leave_policy_form', ['policy' => null]);
    }

    public function store(): void
    {
        $this->validateCsrf('/admin/settings/leave-policy/create');

        $name = trim($_POST['name'] ?? '');
        $legalDays = (float) ($_POST['legal_days'] ?? 0);
        $isStatutory = isset($_POST['is_statutory']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'El nombre es obligatorio.';
            header('Location: /admin/settings/leave-policy/create');
            exit;
        }

        if ($legalDays < 0) {
            $_SESSION['flash_error'] = 'Los días deben ser un número positivo o cero.';
            header('Location: /admin/settings/leave-policy/create');
            exit;
        }

        $categoryKey = $this->generateKey($name);
        $db = Database::getInstance();

        // Ensure uniqueness
        if ($db->fetchOne('SELECT id FROM leave_policy WHERE category_key = ?', [$categoryKey])) {
            $categoryKey .= '_' . bin2hex(random_bytes(4));
        }

        $db->insert('leave_policy', [
            'category_key'       => $categoryKey,
            'name'               => $name,
            'legal_days'         => $legalDays,
            'is_statutory'       => $isStatutory,
            'min_statutory_days' => $isStatutory ? $legalDays : 0,
        ]);

        $_SESSION['flash_success'] = 'Categoría creada correctamente.';
        header('Location: /admin/settings/leave-policy');
        exit;
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $policy = $db->fetchOne('SELECT * FROM leave_policy WHERE id = ?', [(int) $id]);

        if (!$policy) {
            $_SESSION['flash_error'] = 'Categoría no encontrada.';
            header('Location: /admin/settings/leave-policy');
            exit;
        }

        View::render('admin.settings.leave_policy_form', ['policy' => $policy]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf("/admin/settings/leave-policy/{$id}/edit");

        $db = Database::getInstance();
        $policy = $db->fetchOne('SELECT * FROM leave_policy WHERE id = ?', [(int) $id]);

        if (!$policy) {
            $_SESSION['flash_error'] = 'Categoría no encontrada.';
            header('Location: /admin/settings/leave-policy');
            exit;
        }

        $legalDays = (float) ($_POST['legal_days'] ?? 0);

        if ($legalDays < 0) {
            $_SESSION['flash_error'] = 'Los días deben ser un número positivo o cero.';
            header("Location: /admin/settings/leave-policy/{$id}/edit");
            exit;
        }

        if ($policy['is_statutory']) {
            // Statutory categories: only days can be changed, and only upward
            if ($legalDays < (float) $policy['min_statutory_days']) {
                $_SESSION['flash_error'] = sprintf(
                    'No se pueden reducir los días por debajo del mínimo legal establecido por el Estatuto de los Trabajadores (%s días).',
                    rtrim(rtrim(number_format((float) $policy['min_statutory_days'], 1), '0'), '.')
                );
                header("Location: /admin/settings/leave-policy/{$id}/edit");
                exit;
            }

            $db->update('leave_policy', ['legal_days' => $legalDays], 'id = ?', [(int) $id]);
        } else {
            $name = trim($_POST['name'] ?? '');
            $isStatutory = isset($_POST['is_statutory']) ? 1 : 0;

            if ($name === '') {
                $_SESSION['flash_error'] = 'El nombre es obligatorio.';
                header("Location: /admin/settings/leave-policy/{$id}/edit");
                exit;
            }

            $data = [
                'name'               => $name,
                'legal_days'         => $legalDays,
                'is_statutory'       => $isStatutory,
                'min_statutory_days' => $isStatutory ? $legalDays : 0,
            ];

            $db->update('leave_policy', $data, 'id = ?', [(int) $id]);
        }

        $_SESSION['flash_success'] = 'Categoría actualizada correctamente.';
        header('Location: /admin/settings/leave-policy');
        exit;
    }

    public function delete(string $id): void
    {
        $this->validateCsrf('/admin/settings/leave-policy');

        $db = Database::getInstance();
        $policy = $db->fetchOne('SELECT * FROM leave_policy WHERE id = ?', [(int) $id]);

        if (!$policy) {
            $_SESSION['flash_error'] = 'Categoría no encontrada.';
            header('Location: /admin/settings/leave-policy');
            exit;
        }

        if ($policy['is_statutory']) {
            $_SESSION['flash_error'] = 'No se pueden eliminar categorías del Estatuto de los Trabajadores.';
            header('Location: /admin/settings/leave-policy');
            exit;
        }

        $db->query('DELETE FROM leave_policy WHERE id = ?', [(int) $id]);

        $_SESSION['flash_success'] = 'Categoría eliminada correctamente.';
        header('Location: /admin/settings/leave-policy');
        exit;
    }

    private function validateCsrf(string $redirectOnFail): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['flash_error'] = 'Solicitud no válida. Inténtalo de nuevo.';
            header('Location: ' . $redirectOnFail);
            exit;
        }
    }

    private function generateKey(string $name): string
    {
        $key = mb_strtolower($name, 'UTF-8');
        // Replace accented characters
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        ];
        $key = strtr($key, $map);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');
        return substr($key ?: 'category', 0, 50);
    }
}
