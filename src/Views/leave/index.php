<?php
$title = 'Leave Management';
$formatDays = static fn(float $value): string => rtrim(rtrim(number_format($value, 1), '0'), '.');
$locale = \App\Core\I18n::getLocale();
$dateFormatsByLocale = [
    'en' => 'M j, Y',
    'es' => 'd/m/Y',
    'ca' => 'd/m/Y',
    'eu' => 'Y/m/d',
    'gl' => 'd/m/Y',
];
$dateFormat = $dateFormatsByLocale[$locale] ?? 'Y-m-d';
$formatDate = static function (string $dateValue) use ($dateFormat): string {
    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return '—';
    }

    return htmlspecialchars(date($dateFormat, $timestamp));
};
$formatLeaveType = static function (string $leaveType) use ($t): string {
    $translationKey = 'leave.type.' . $leaveType;
    $translated = $t($translationKey);

    if ($translated !== $translationKey) {
        return $translated;
    }

    return ucfirst(str_replace('_', ' ', $leaveType));
};
?>

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

<?php if (!empty($categoryTracking)): ?>
<div class="card">
    <h2>Balance by Leave Category</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Available</th>
                <th>Used</th>
                <th>Remaining</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categoryTracking as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td><?= $category['tracks_balance'] ? $formatDays((float) $category['total_days']) : $t('leave.not_applicable') ?></td>
                <td><?= $formatDays((float) $category['used_days']) ?></td>
                <td><?= $category['tracks_balance'] ? $formatDays((float) $category['available_days']) : $t('leave.not_applicable') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h2><?= htmlspecialchars($t('leave.team_calendar.title')) ?></h2>
    <?php if (empty($teamAbsences)): ?>
        <p class="text-muted"><?= htmlspecialchars($t('leave.team_calendar.empty')) ?></p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th><?= htmlspecialchars($t('leave.team_calendar.employee')) ?></th>
                <th><?= htmlspecialchars($t('leave.team_calendar.type')) ?></th>
                <th><?= htmlspecialchars($t('leave.team_calendar.from')) ?></th>
                <th><?= htmlspecialchars($t('leave.team_calendar.to')) ?></th>
                <th><?= htmlspecialchars($t('leave.team_calendar.days')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teamAbsences as $absence): ?>
            <tr>
                <td><?= htmlspecialchars($absence['first_name'] . ' ' . $absence['last_name']) ?></td>
                <td><?= htmlspecialchars($formatLeaveType((string) $absence['leave_type'])) ?></td>
                <td><?= $formatDate((string) $absence['start_date']) ?></td>
                <td><?= $formatDate((string) $absence['end_date']) ?></td>
                <td><?= $formatDays((float) $absence['calculated_days']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>My Leave Requests</h2>
    <?php if (empty($requests)): ?>
        <p class="text-muted">No leave requests found.</p>
    <?php else: ?>
    <?php $today = strtotime(date('Y-m-d')); ?>
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
            <?php $canManage = in_array($request['status'], ['pending', 'approved'], true) && strtotime($request['end_date']) >= $today; ?>
            <tr>
                <td><?= ucfirst($request['leave_type']) ?></td>
                <td><?= date('M j, Y', strtotime($request['start_date'])) ?></td>
                <td><?= date('M j, Y', strtotime($request['end_date'])) ?></td>
                <td><?= $formatDays((float) $request['calculated_days']) ?></td>
                <td><span class="badge badge-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span></td>
                <td><?= $request['reviewer_first'] ? htmlspecialchars($request['reviewer_first'] . ' ' . $request['reviewer_last']) : '—' ?></td>
                <td>
                    <?php if ($canManage): ?>
                    <a href="/leave/edit/<?= $request['id'] ?>" class="btn btn-sm btn-outline">Edit Request</a>
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
