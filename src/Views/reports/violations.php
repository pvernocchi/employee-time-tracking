<?php $title = 'Compliance Violations Report'; ?>

<div class="page-header">
    <h1>⚠️ Compliance Violations Report</h1>
    <div class="page-actions">
        <a href="/admin/reports" class="btn btn-outline">← Back to Reports</a>
    </div>
</div>

<?php if (empty($allViolations)): ?>
<div class="card">
    <p class="text-center text-muted">✅ No current compliance violations detected.</p>
</div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Violation</th>
                <th>Severity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allViolations as $item): ?>
                <?php foreach ($item['violations'] as $violation): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['employee']['first_name'] . ' ' . $item['employee']['last_name']) ?></strong></td>
                    <td><?= htmlspecialchars($item['employee']['department'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($violation['message']) ?></td>
                    <td><span class="badge badge-<?= $violation['severity'] === 'danger' ? 'rejected' : 'pending' ?>"><?= ucfirst($violation['severity']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
