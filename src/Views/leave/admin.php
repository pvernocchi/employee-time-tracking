<?php $title = 'Manage Leave Requests'; ?>

<div class="page-header">
    <h1>Leave Requests</h1>
    <div class="page-actions">
        <a href="/admin/leave?status=pending" class="btn btn-sm <?= $currentStatus === 'pending' ? 'btn-primary' : 'btn-outline' ?>">Pending</a>
        <a href="/admin/leave?status=approved" class="btn btn-sm <?= $currentStatus === 'approved' ? 'btn-primary' : 'btn-outline' ?>">Approved</a>
        <a href="/admin/leave?status=rejected" class="btn btn-sm <?= $currentStatus === 'rejected' ? 'btn-primary' : 'btn-outline' ?>">Rejected</a>
        <a href="/admin/leave?status=all" class="btn btn-sm <?= $currentStatus === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
    </div>
</div>

<div class="card">
    <?php if (empty($requests)): ?>
        <p class="text-muted">No leave requests found.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Days</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></td>
                <td><?= htmlspecialchars($request['department'] ?? '—') ?></td>
                <td><?= ucfirst($request['leave_type']) ?></td>
                <td><?= date('M j', strtotime($request['start_date'])) ?></td>
                <td><?= date('M j', strtotime($request['end_date'])) ?></td>
                <td><?= (strtotime($request['end_date']) - strtotime($request['start_date'])) / 86400 + 1 ?></td>
                <td><span class="badge badge-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span></td>
                <td>
                    <?php if ($request['status'] === 'pending'): ?>
                    <div class="btn-group">
                        <form method="POST" action="/admin/leave/approve/<?= $request['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="POST" action="/admin/leave/reject/<?= $request['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
