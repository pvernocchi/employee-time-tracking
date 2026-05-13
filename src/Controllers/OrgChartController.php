<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class OrgChartController
{
    private const MAX_DEPTH = 5;

    public function ownOrgChart(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $this->renderOrgChart((int) $user['id'], false);
    }

    public function adminOrgChart(string $id): void
    {
        Auth::requireAdmin();
        $this->renderOrgChart((int) $id, true);
    }

    private function renderOrgChart(int $userId, bool $isAdmin): void
    {
        $db = Database::getInstance();

        $focusedUser = $db->fetchOne(
            'SELECT id, first_name, last_name, role, department, manager_id FROM users WHERE id = ?',
            [$userId]
        );

        if (!$focusedUser) {
            $_SESSION['flash_error'] = 'User not found.';
            header('Location: ' . ($isAdmin ? '/admin/employees' : '/dashboard'));
            exit;
        }

        $managerChain = $this->buildManagerChain($db, $focusedUser);
        $children     = $this->buildSubtree($db, $userId, 0);
        $tree         = $this->buildFullTree($managerChain, $focusedUser, $children);

        View::render('orgchart.index', [
            'tree'            => $tree,
            'focusedUserId'   => $userId,
            'focusedUserName' => $focusedUser['first_name'] . ' ' . $focusedUser['last_name'],
            'isAdmin'         => $isAdmin,
        ]);
    }

    /**
     * Build the manager chain (from topmost ancestor down to the direct manager).
     * Returns array ordered topmost first.
     */
    private function buildManagerChain(Database $db, array $user): array
    {
        $chain   = [];
        $visited = [(int) $user['id']];
        $currentManagerId = isset($user['manager_id']) ? (int) $user['manager_id'] : null;

        while ($currentManagerId !== null) {
            if (in_array($currentManagerId, $visited, true)) {
                break; // prevent circular references
            }
            $visited[] = $currentManagerId;

            $manager = $db->fetchOne(
                'SELECT id, first_name, last_name, role, department, manager_id FROM users WHERE id = ?',
                [$currentManagerId]
            );

            if (!$manager) {
                break;
            }

            $chain[]          = $manager;
            $currentManagerId = isset($manager['manager_id']) ? (int) $manager['manager_id'] : null;
        }

        return array_reverse($chain); // topmost first
    }

    /**
     * Build the subtree of direct and indirect reports (downward).
     */
    private function buildSubtree(Database $db, int $userId, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];
        }

        $reports = $db->fetchAll(
            'SELECT id, first_name, last_name, role, department FROM users WHERE manager_id = ? ORDER BY last_name, first_name',
            [$userId]
        );

        foreach ($reports as &$report) {
            $report['focused']  = false;
            $report['children'] = $this->buildSubtree($db, (int) $report['id'], $depth + 1);
        }

        return $reports;
    }

    /**
     * Combine manager chain + focused user + subtree into a single nested tree.
     * The manager chain becomes a linear single-child chain leading to the focused node.
     */
    private function buildFullTree(array $managerChain, array $focusedUser, array $children): array
    {
        $focusedNode = [
            'id'         => $focusedUser['id'],
            'first_name' => $focusedUser['first_name'],
            'last_name'  => $focusedUser['last_name'],
            'role'       => $focusedUser['role'],
            'department' => $focusedUser['department'] ?? '',
            'focused'    => true,
            'children'   => $children,
        ];

        if (empty($managerChain)) {
            return $focusedNode;
        }

        // Wrap the focused node inside the chain from bottom to top.
        // managerChain is [topmost, ..., direct_manager].
        // We iterate in reverse (direct_manager first) to wrap upward.
        $current = $focusedNode;
        foreach (array_reverse($managerChain) as $manager) {
            $current = [
                'id'         => $manager['id'],
                'first_name' => $manager['first_name'],
                'last_name'  => $manager['last_name'],
                'role'       => $manager['role'],
                'department' => $manager['department'] ?? '',
                'focused'    => false,
                'children'   => [$current],
            ];
        }

        return $current;
    }
}
