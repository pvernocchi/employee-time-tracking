<?php $title = 'MFA Methods – ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>

<div class="page-header">
    <h1>🔑 MFA Methods</h1>
    <p><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> &lt;<?= htmlspecialchars($user['email']) ?>&gt;</p>
    <div class="page-actions">
        <a href="/admin/employees" class="btn btn-outline">← Back to Employees</a>
    </div>
</div>

<!-- Per-user MFA requirement override -->
<div class="card">
    <h2>MFA Requirement</h2>
    <form method="POST" action="/admin/security/users/<?= $user['id'] ?>/mfa/toggle">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="mfa_required" value="1"
                    <?= $user['mfa_required'] ? 'checked' : '' ?>>
                Force MFA for this user (overrides global policy)
            </label>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>

<!-- Enrolled MFA methods -->
<div class="card mt-2">
    <h2>Enrolled Methods (<?= count($methods) ?>)</h2>
    <?php if (empty($methods)): ?>
        <p class="text-muted">No MFA methods enrolled.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Enrolled</th>
                    <th>Last Used</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $m): ?>
                <tr>
                    <td><?= htmlspecialchars(strtoupper($m['type'])) ?></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><span class="badge badge-<?= $m['is_active'] ? 'approved' : 'rejected' ?>"><?= $m['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= $m['last_used_at'] ? date('d/m/Y H:i', strtotime($m['last_used_at'])) : '—' ?></td>
                    <td>
                        <form method="POST" action="/admin/security/mfa/<?= $m['id'] ?>/revoke" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline"
                                onclick="return confirm('Revoke this MFA method?')">Revoke</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
