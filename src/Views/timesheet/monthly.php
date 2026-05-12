<?php $title = 'Monthly Timesheet'; ?>

<div class="page-header">
    <h1>Monthly Timesheet</h1>
    <div class="page-actions">
        <a href="/timesheet/monthly?month=<?= $monthOffset - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
        <span class="date-range"><?= $monthName ?></span>
        <a href="/timesheet/monthly?month=<?= $monthOffset + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
        <?php if ($monthOffset !== 0): ?>
            <a href="/timesheet/monthly" class="btn btn-sm btn-outline">Current</a>
        <?php endif; ?>
    </div>
</div>

<div class="timesheet-nav">
    <a href="/timesheet/weekly" class="btn btn-sm btn-outline">Weekly</a>
    <a href="/timesheet/monthly?month=<?= $monthOffset ?>" class="btn btn-sm btn-primary">Monthly</a>
</div>

<div class="card">
    <div class="month-summary">
        <p>Total Hours: <strong><?= $totalMonthHours ?> hrs</strong></p>
        <p>Days Worked: <strong><?= count($dailyData) ?></strong></p>
        <?php if (count($dailyData) > 0): ?>
        <p>Avg Hours/Day: <strong><?= round($totalMonthHours / count($dailyData), 2) ?> hrs</strong></p>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Hours</th>
                <th>Entries</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $currentDate = $firstDay;
            while ($currentDate <= $lastDay):
                $isWeekend = in_array(date('N', strtotime($currentDate)), [6, 7]);
                $data = $dailyData[$currentDate] ?? null;
            ?>
            <tr class="<?= $currentDate === date('Y-m-d') ? 'row-today' : '' ?> <?= $isWeekend ? 'row-weekend' : '' ?>">
                <td><?= date('M j', strtotime($currentDate)) ?></td>
                <td><?= date('D', strtotime($currentDate)) ?></td>
                <td><?= $data ? '<strong>' . $data['hours'] . '</strong>' : '<span class="text-muted">—</span>' ?></td>
                <td><?= $data ? $data['entries'] : '' ?></td>
            </tr>
            <?php
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            endwhile;
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td><strong><?= $totalMonthHours ?> hrs</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
