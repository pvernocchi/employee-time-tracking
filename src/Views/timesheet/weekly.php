<?php $title = 'Weekly Timesheet'; ?>

<div class="page-header">
    <h1>Weekly Timesheet</h1>
    <div class="page-actions">
        <a href="/timesheet/weekly?week=<?= $weekOffset - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
        <span class="date-range"><?= date('M j', strtotime($mondayDate)) ?> – <?= date('M j, Y', strtotime($sundayDate)) ?></span>
        <a href="/timesheet/weekly?week=<?= $weekOffset + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
        <?php if ($weekOffset !== 0): ?>
            <a href="/timesheet/weekly" class="btn btn-sm btn-outline">Today</a>
        <?php endif; ?>
    </div>
</div>

<div class="timesheet-nav">
    <a href="/timesheet/weekly?week=<?= $weekOffset ?>" class="btn btn-sm btn-primary">Weekly</a>
    <a href="/timesheet/monthly" class="btn btn-sm btn-outline">Monthly</a>
    <a href="/timesheet/export" class="btn btn-sm btn-outline">📤 Export My Records</a>
</div>

<?php if ($weeklyWarning): ?>
<div class="alert alert-warning">
    <strong>⚠️ Weekly hours exceeded:</strong> You have worked <?= $totalWeekHours ?>h this week. The legal maximum is 40h/week (Art. 34 ET).
</div>
<?php endif; ?>

<?php if ($yearOvertime > 60): ?>
<div class="alert alert-<?= $yearOvertime >= 80 ? 'error' : 'warning' ?>">
    <strong>⚠️ Annual overtime:</strong> <?= $yearOvertime ?>h of 80h maximum (Art. 35.2 ET).
    <?php if ($yearOvertime >= 80): ?> <strong>LIMIT EXCEEDED.</strong><?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Date</th>
                <th>Entries</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days as $day): ?>
            <tr class="<?= $day['date'] === date('Y-m-d') ? 'row-today' : '' ?> <?= $day['totalHours'] > 9 ? 'row-warning' : '' ?>">
                <td><strong><?= $day['dayName'] ?></strong></td>
                <td><?= date('M j', strtotime($day['date'])) ?></td>
                <td>
                    <?php if (!empty($day['entries'])): ?>
                        <?php foreach ($day['entries'] as $entry): ?>
                            <div class="entry-line">
                                <?= date('g:i A', strtotime($entry['clock_in'])) ?>
                                –
                                <?= $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '<span class="badge badge-success">Active</span>' ?>
                                <?php if ($entry['break_minutes']): ?>
                                    <small>(<?= $entry['break_minutes'] ?>min break)</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= $day['totalHours'] > 0 ? round($day['totalHours'], 2) : '—' ?></strong>
                    <?php if ($day['totalHours'] > 9): ?>
                        <span class="badge badge-pending">⚠️ >9h</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td>
                    <strong class="<?= $totalWeekHours > 40 ? 'text-danger' : '' ?>"><?= $totalWeekHours ?> hrs</strong>
                    <?php if ($totalWeekHours > 40): ?>
                        <span class="badge badge-rejected">Exceeds 40h</span>
                    <?php endif; ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
