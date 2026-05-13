<?php $title = 'Manage Employees'; ?>

<div class="page-header">
    <h1>Employees</h1>
    <div class="page-actions">
        <?php if ($isAdmin): ?>
        <a href="/admin/employees/create" class="btn btn-primary">+ Add Employee</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Manager</th>
                <th>Department</th>
                <th>Rate</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr class="<?= !$emp['is_active'] ? 'row-inactive' : '' ?>">
                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                <td><?= htmlspecialchars($emp['email']) ?></td>
                <td><span class="badge badge-role-<?= $emp['role'] ?>"><?= ucfirst($emp['role']) ?></span></td>
                <td>
                    <?php if (!empty($emp['manager_first_name']) || !empty($emp['manager_last_name'])): ?>
                        <?= htmlspecialchars(trim(($emp['manager_first_name'] ?? '') . ' ' . ($emp['manager_last_name'] ?? ''))) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($emp['department'] ?? '—') ?></td>
                <td><?= $emp['hourly_rate'] ? '$' . number_format($emp['hourly_rate'], 2) : '—' ?></td>
                <td><span class="badge badge-<?= $emp['is_active'] ? 'active' : 'inactive' ?>"><?= $emp['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <a href="/admin/employees/edit/<?= $emp['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <?php if ($isAdmin): ?>
                    <a href="/admin/security/users/<?= $emp['id'] ?>/mfa" class="btn btn-sm btn-outline" title="Manage MFA">🔑</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
