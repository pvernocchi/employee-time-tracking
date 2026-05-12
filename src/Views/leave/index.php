<?php $title = 'Leave Management'; ?>

<div class="page-header">
    <h1>Leave Management</h1>
    <div class="page-actions">
        <a href="/leave/request" class="btn btn-primary">+ New Request</a>
    </div>
</div>

<?php if (!empty($balances)): ?>
<div class="stats-grid">
    <?php foreach ($balances as $balance): ?>
    <div class="stat-card">
        <div class="stat-content">
            <h3><?= ucfirst($balance['leave_type']) ?></h3>
            <p class="stat-value"><?= $balance['total_days'] - $balance['used_days'] ?> / <?= $balance['total_days'] ?></p>
            <small>days remaining</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <h2>My Leave Requests</h2>
    <?php if (empty($requests)): ?>
        <p class="text-muted">No leave requests found.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Days</th>
                <th>Status</th>
                <th>Reviewed By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= ucfirst($request['leave_type']) ?></td>
                <td><?= date('M j, Y', strtotime($request['start_date'])) ?></td>
                <td><?= date('M j, Y', strtotime($request['end_date'])) ?></td>
                <td><?= (strtotime($request['end_date']) - strtotime($request['start_date'])) / 86400 + 1 ?></td>
                <td><span class="badge badge-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span></td>
                <td><?= $request['reviewer_first'] ? htmlspecialchars($request['reviewer_first'] . ' ' . $request['reviewer_last']) : '—' ?></td>
                <td>
                    <?php if ($request['status'] === 'pending'): ?>
                    <form method="POST" action="/leave/cancel/<?= $request['id'] ?>" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this request?')">Cancel</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
