<?php $title = 'Labor Inspector - Overview'; ?>

<div class="page-header">
    <h1>🔍 Inspección de Trabajo - Registro de Jornada</h1>
    <div class="page-actions">
        <a href="/inspector/export?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-primary">📤 Export All Records</a>
    </div>
</div>

<div class="card">
    <h2>Employee Directory</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Email</th>
                <th>Department</th>
                <th>Total Entries</th>
                <th>Last Activity</th>
                <th>Actions</th>
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
                <td><a href="/inspector/employee/<?= $emp['id'] ?>" class="btn btn-sm btn-outline">View Records</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
