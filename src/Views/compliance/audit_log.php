<?php $title = 'Audit Log'; ?>

<div class="page-header">
    <h1>📝 Audit Log</h1>
    <div class="page-actions">
        <a href="/compliance" class="btn btn-outline">← Back to Compliance</a>
    </div>
</div>

<div class="card">
    <p class="text-muted">Total records: <?= $totalCount ?></p>
    <table class="table">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Performed By</th>
                <th>Action</th>
                <th>Employee</th>
                <th>Entry</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= date('d/m/Y H:i:s', strtotime($entry['created_at'])) ?></td>
                <td><?= htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']) ?></td>
                <td><span class="badge badge-<?= $entry['action'] === 'create' ? 'approved' : ($entry['action'] === 'delete' ? 'rejected' : 'pending') ?>"><?= ucfirst($entry['action']) ?></span></td>
                <td><?= htmlspecialchars($entry['entry_user_first'] . ' ' . $entry['entry_user_last']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($entry['clock_in'])) ?></td>
                <td><small><?= htmlspecialchars($entry['ip_address'] ?? '—') ?></small></td>
                <td>
                    <?php if ($entry['old_values']): ?>
                        <small title="<?= htmlspecialchars($entry['old_values']) ?>">📋 Changes</small>
                    <?php else: ?>
                        <small>—</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php $totalPages = ceil($totalCount / $perPage); ?>
    <?php if ($totalPages > 1): ?>
    <div class="form-actions">
        <?php if ($page > 1): ?>
            <a href="/compliance/audit?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="/compliance/audit?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
