<?php $title = 'Report Results'; ?>

<div class="page-header">
    <h1>Report Results</h1>
    <div class="page-actions">
        <a href="/admin/reports" class="btn btn-outline">← Back</a>
        <a href="/admin/reports/export?<?= http_build_query(array_merge($_GET, ['format' => 'csv'])) ?>" class="btn btn-primary">Download CSV</a>
    </div>
</div>

<div class="card">
    <p class="text-muted">
        Showing entries from <strong><?= date('M j, Y', strtotime($startDate)) ?></strong> to <strong><?= date('M j, Y', strtotime($endDate)) ?></strong>
        (<?= count($entries) ?> entries)
    </p>

    <?php if (empty($entries)): ?>
        <p class="text-muted">No entries found for this period.</p>
    <?php else: ?>
    <?php
        $totalHours = 0;
    ?>
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Date</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Break</th>
                <th>Hours</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry):
                $hours = 0;
                if ($entry['clock_out']) {
                    $diff = strtotime($entry['clock_out']) - strtotime($entry['clock_in']);
                    $hours = round(($diff / 3600) - ($entry['break_minutes'] / 60), 2);
                    $totalHours += $hours;
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']) ?></td>
                <td><?= htmlspecialchars($entry['department'] ?? '—') ?></td>
                <td><?= date('M j', strtotime($entry['clock_in'])) ?></td>
                <td><?= date('g:i A', strtotime($entry['clock_in'])) ?></td>
                <td><?= $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : 'Active' ?></td>
                <td><?= $entry['break_minutes'] ?> min</td>
                <td><strong><?= $hours ?></strong></td>
                <td><?= htmlspecialchars($entry['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"><strong>Total</strong></td>
                <td><strong><?= round($totalHours, 2) ?> hrs</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>
