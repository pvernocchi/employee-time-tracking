<?php $title = 'Dashboard'; ?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars(\App\Core\Auth::user()['first_name']) ?>!</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-content">
            <h3>Today</h3>
            <p class="stat-value"><?= $todayHours ?> hrs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <h3>This Week</h3>
            <p class="stat-value"><?= $weekHours ?> hrs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><?= $activeEntry ? '🟢' : '🔴' ?></div>
        <div class="stat-content">
            <h3>Status</h3>
            <p class="stat-value"><?= $activeEntry ? 'Clocked In' : 'Clocked Out' ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏖️</div>
        <div class="stat-content">
            <h3>Pending Leave</h3>
            <p class="stat-value"><?= count($pendingLeave) ?></p>
        </div>
    </div>
</div>

<?php if ($activeEntry): ?>
<div class="card">
    <h2>Currently Clocked In</h2>
    <p>Since: <strong><?= date('g:i A', strtotime($activeEntry['clock_in'])) ?></strong></p>
    <p>Duration: <strong id="live-duration" data-start="<?= $activeEntry['clock_in'] ?>">--:--</strong></p>
    <form method="POST" action="/clock/out" class="inline-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="break_minutes">Break (min)</label>
                <input type="number" name="break_minutes" id="break_minutes" value="0" min="0" class="input-sm">
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <input type="text" name="notes" id="notes" placeholder="Optional notes..." class="input-md">
            </div>
            <button type="submit" class="btn btn-danger">Clock Out</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <h2>Ready to Work?</h2>
    <form method="POST" action="/clock/in">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" class="btn btn-success btn-lg">Clock In</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top: 1.5rem;">
    <h2>🏢 <?= htmlspecialchars($t('orgchart.heading')) ?></h2>
    <p class="text-muted"><?= htmlspecialchars($t('orgchart.dashboard_description')) ?></p>
    <div class="form-actions mt-2">
        <a href="/orgchart" class="btn btn-outline">🏢 <?= htmlspecialchars($t('orgchart.view_orgchart')) ?></a>
    </div>
</div>

<?php if (\App\Core\Auth::isManager() && !empty($adminData)): ?>
<div class="page-header" style="margin-top: 2rem;">
    <h2>Admin Overview</h2>
</div>
<div class="stats-grid">
    <div class="stat-card stat-card-info">
        <div class="stat-content">
            <h3>Active Employees</h3>
            <p class="stat-value"><?= $adminData['active_employees'] ?></p>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-content">
            <h3>Currently Clocked In</h3>
            <p class="stat-value"><?= $adminData['clocked_in_count'] ?></p>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-content">
            <h3>Pending Leave Requests</h3>
            <p class="stat-value"><a href="/admin/leave"><?= $adminData['pending_leave_count'] ?></a></p>
        </div>
    </div>
</div>
<?php endif; ?>
