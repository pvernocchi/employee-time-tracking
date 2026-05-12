<?php $title = 'Compliance Dashboard'; ?>

<div class="page-header">
    <h1>📋 Compliance Dashboard</h1>
    <p>Spanish Labor Law Compliance (Real Decreto-ley 8/2019)</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <h3>Active Employees</h3>
            <p class="stat-value"><?= $totalEmployees ?></p>
        </div>
    </div>
    <div class="stat-card <?= $violationCount > 0 ? 'stat-card-warning' : '' ?>">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <h3>Current Violations</h3>
            <p class="stat-value"><?= $violationCount ?></p>
        </div>
    </div>
</div>

<?php if (!empty($allAlerts)): ?>
<div class="card mt-2">
    <h2>⚠️ Active Compliance Alerts</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Alert</th>
                <th>Severity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allAlerts as $item): ?>
                <?php foreach ($item['alerts'] as $alert): ?>
                <tr>
                    <td><?= htmlspecialchars($item['employee']['first_name'] . ' ' . $item['employee']['last_name']) ?></td>
                    <td><?= htmlspecialchars($item['employee']['department'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($alert['message']) ?></td>
                    <td><span class="badge badge-<?= $alert['severity'] === 'danger' ? 'rejected' : 'pending' ?>"><?= ucfirst($alert['severity']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="card mt-2">
    <p class="text-center text-muted">✅ No compliance violations detected.</p>
</div>
<?php endif; ?>

<?php if (!empty($recentAudit)): ?>
<div class="card mt-2">
    <h2>📝 Recent Audit Activity</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
                <th>Entry Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentAudit as $audit): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($audit['created_at'])) ?></td>
                <td><?= htmlspecialchars($audit['first_name'] . ' ' . $audit['last_name']) ?></td>
                <td><span class="badge badge-<?= $audit['action'] === 'create' ? 'approved' : 'pending' ?>"><?= ucfirst($audit['action']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($audit['clock_in'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="form-actions">
        <a href="/compliance/audit" class="btn btn-outline">View Full Audit Log →</a>
    </div>
</div>
<?php endif; ?>

<div class="card mt-2">
    <h2>📤 Export for Labor Inspection</h2>
    <form method="GET" action="/compliance/export-inspection">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= date('Y-01-01') ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Export CSV for Inspection</button>
    </form>
</div>
