<?php $title = 'Inspector - ' . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>

<div class="page-header">
    <h1>🔍 <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h1>
    <div class="page-actions">
        <a href="/inspector" class="btn btn-outline">← Back to Overview</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <h3>Email</h3>
            <p><?= htmlspecialchars($employee['email']) ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <h3>Department</h3>
            <p><?= htmlspecialchars($employee['department'] ?? '—') ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <h3>Employed Since</h3>
            <p><?= date('d/m/Y', strtotime($employee['created_at'])) ?></p>
        </div>
    </div>
</div>

<div class="card mt-2">
    <h2>Filter Records</h2>
    <form method="GET" action="/inspector/employee/<?= $employee['id'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">From</label>
                <input type="date" name="start_date" id="start_date" value="<?= $startDate ?>">
            </div>
            <div class="form-group">
                <label for="end_date">To</label>
                <input type="date" name="end_date" id="end_date" value="<?= $endDate ?>">
            </div>
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="card mt-2">
    <h2>Time Records (<?= count($entries) ?> entries)</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Break (min)</th>
                <th>Hours</th>
                <th>Status</th>
                <th>Locked</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($entry['clock_in'])) ?></td>
                <td><?= date('H:i', strtotime($entry['clock_in'])) ?></td>
                <td><?= $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : '<span class="badge badge-success">Active</span>' ?></td>
                <td><?= $entry['break_minutes'] ?></td>
                <td>
                    <?php if ($entry['clock_out']): ?>
                        <?= round((strtotime($entry['clock_out']) - strtotime($entry['clock_in'])) / 3600 - $entry['break_minutes'] / 60, 2) ?>h
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $entry['status'] ?>"><?= ucfirst($entry['status']) ?></span></td>
                <td><?= $entry['is_locked'] ? '🔒' : '—' ?></td>
                <td><?= htmlspecialchars($entry['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($auditTrail)): ?>
<div class="card mt-2">
    <h2>📝 Audit Trail</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Performed By</th>
                <th>Action</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($auditTrail as $audit): ?>
            <tr>
                <td><?= date('d/m/Y H:i:s', strtotime($audit['created_at'])) ?></td>
                <td><?= htmlspecialchars($audit['first_name'] . ' ' . $audit['last_name']) ?></td>
                <td><span class="badge badge-<?= $audit['action'] === 'create' ? 'approved' : 'pending' ?>"><?= ucfirst($audit['action']) ?></span></td>
                <td><small><?= htmlspecialchars($audit['ip_address'] ?? '—') ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
