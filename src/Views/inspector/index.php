<?php $title = $t('inspector.overview_title'); ?>

<div class="page-header">
    <h1>🔍 <?= htmlspecialchars($t('inspector.overview_heading')) ?></h1>
    <div class="page-actions">
        <a href="/inspector/export?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-primary"><?= htmlspecialchars($t('inspector.export_all_records')) ?></a>
    </div>
</div>

<div class="card">
    <h2><?= htmlspecialchars($t('inspector.employee_directory')) ?></h2>
    <table class="table">
        <thead>
            <tr>
                <th><?= htmlspecialchars($t('inspector.csv.employee')) ?></th>
                <th><?= htmlspecialchars($t('inspector.csv.email')) ?></th>
                <th><?= htmlspecialchars($t('inspector.csv.department')) ?></th>
                <th><?= htmlspecialchars($t('inspector.total_entries')) ?></th>
                <th><?= htmlspecialchars($t('inspector.last_activity')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.actions')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($emp['email']) ?></td>
                <td><?= htmlspecialchars($emp['department'] ?? '—') ?></td>
                <td><?= $emp['total_entries'] ?></td>
                <td><?= $emp['last_clock_in'] ? date('d/m/Y H:i', strtotime($emp['last_clock_in'])) : '—' ?></td>
                <td><a href="/inspector/employee/<?= $emp['id'] ?>" class="btn btn-sm btn-outline"><?= htmlspecialchars($t('inspector.view_records')) ?></a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
