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
</div>

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
            <tr class="<?= $day['date'] === date('Y-m-d') ? 'row-today' : '' ?>">
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
                <td><strong><?= $day['totalHours'] > 0 ? round($day['totalHours'], 2) : '—' ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong><?= $totalWeekHours ?> hrs</strong></td>
            </tr>
        </tfoot>
    </table>
</div>
