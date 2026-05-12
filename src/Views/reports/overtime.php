<?php $title = 'Overtime Report'; ?>

<div class="page-header">
    <h1>📊 Overtime Report - <?= $year ?></h1>
    <div class="page-actions">
        <a href="/admin/reports" class="btn btn-outline">← Back to Reports</a>
        <a href="/admin/reports/overtime?year=<?= $year - 1 ?>" class="btn btn-sm btn-outline"><?= $year - 1 ?></a>
        <a href="/admin/reports/overtime?year=<?= $year + 1 ?>" class="btn btn-sm btn-outline"><?= $year + 1 ?></a>
    </div>
</div>

<div class="card">
    <p class="text-muted">Annual overtime limit: 80 hours (Art. 35.2 Estatuto de los Trabajadores)</p>
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Overtime Hours</th>
                <th>Remaining</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($overtimeData as $item): ?>
            <tr class="<?= $item['limit_exceeded'] ? 'row-warning' : '' ?>">
                <td><strong><?= htmlspecialchars($item['employee']['first_name'] . ' ' . $item['employee']['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($item['employee']['department'] ?? '—') ?></td>
                <td><?= $item['overtime_hours'] ?>h</td>
                <td><?= max(0, 80 - $item['overtime_hours']) ?>h</td>
                <td>
                    <?php if ($item['limit_exceeded']): ?>
                        <span class="badge badge-rejected">⚠️ Exceeded</span>
                    <?php elseif ($item['overtime_hours'] > 60): ?>
                        <span class="badge badge-pending">Approaching</span>
                    <?php else: ?>
                        <span class="badge badge-approved">OK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
