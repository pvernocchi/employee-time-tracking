<?php $title = 'Clock In/Out'; ?>

<div class="page-header">
    <h1>Clock In / Out</h1>
</div>

<?php if (!empty($complianceWarnings)): ?>
<div class="alert alert-warning">
    <strong>⚠️ Compliance Alerts:</strong>
    <ul style="margin: 0.5rem 0 0 1rem;">
        <?php foreach ($complianceWarnings as $warning): ?>
            <li><?= htmlspecialchars($warning['message']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_warning'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['flash_warning']) ?></div>
    <?php unset($_SESSION['flash_warning']); ?>
<?php endif; ?>

<?php if ($activeEntry): ?>
<div class="card card-highlight">
    <h2>🟢 You are clocked in</h2>
    <p>Started: <strong><?= date('g:i A', strtotime($activeEntry['clock_in'])) ?></strong></p>
    <p>Duration: <strong id="live-duration" data-start="<?= $activeEntry['clock_in'] ?>">--:--</strong></p>
    
    <form method="POST" action="/clock/out" class="mt-1">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="break_minutes">Break Duration (minutes)</label>
                <input type="number" name="break_minutes" id="break_minutes" value="0" min="0" max="480">
            </div>
            <div class="form-group">
                <label for="notes">Notes (optional)</label>
                <input type="text" name="notes" id="notes" placeholder="What did you work on?">
            </div>
        </div>
        <button type="submit" class="btn btn-danger btn-lg">Clock Out</button>
    </form>
</div>
<?php else: ?>
<div class="card text-center">
    <h2>🔴 You are not clocked in</h2>
    <p>Click the button below to start tracking your time.</p>
    <form method="POST" action="/clock/in">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" class="btn btn-success btn-lg">Clock In</button>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($entries)): ?>
<div class="card mt-2">
    <h2>Today's Entries</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Break</th>
                <th>Hours</th>
                <th>Notes</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= date('g:i A', strtotime($entry['clock_in'])) ?></td>
                <td><?= $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '<span class="badge badge-success">Active</span>' ?></td>
                <td><?= $entry['break_minutes'] ?> min</td>
                <td>
                    <?php if ($entry['clock_out']): ?>
                        <?= round((strtotime($entry['clock_out']) - strtotime($entry['clock_in'])) / 3600 - $entry['break_minutes'] / 60, 2) ?> hrs
                    <?php else: ?>
                        --
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($entry['notes'] ?? '') ?></td>
                <td><span class="badge badge-<?= $entry['status'] ?>"><?= ucfirst($entry['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
