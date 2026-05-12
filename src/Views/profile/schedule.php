<?php $title = $t('profile.work_schedule') . ' – ' . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>

<div class="page-header">
    <h1>📅 <?= htmlspecialchars($t('profile.work_schedule')) ?></h1>
    <p><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?> (<?= htmlspecialchars($employee['email']) ?>)</p>
    <div class="page-actions">
        <a href="/admin/employees" class="btn btn-outline">← <?= htmlspecialchars($t('profile.back_to_employees')) ?></a>
    </div>
</div>

<form method="POST" action="/admin/employees/<?= (int) $employee['id'] ?>/schedule">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <table class="table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($t('profile.day')) ?></th>
                    <th><?= htmlspecialchars($t('profile.working')) ?></th>
                    <th><?= htmlspecialchars($t('profile.start_time')) ?></th>
                    <th><?= htmlspecialchars($t('profile.end_time')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $idx => $dayKey): ?>
                    <?php $day = $schedule[$idx]; ?>
                    <tr>
                        <td><?= htmlspecialchars($t('profile.day_' . $dayKey)) ?></td>
                        <td>
                            <input type="checkbox" name="working_<?= $idx ?>" value="1"
                                <?= !empty($day['is_working']) ? 'checked' : '' ?>
                                onchange="toggleDayRow(<?= $idx ?>, this.checked)">
                        </td>
                        <td>
                            <input type="time" name="start_<?= $idx ?>" id="start_<?= $idx ?>"
                                value="<?= htmlspecialchars(substr($day['start_time'], 0, 5)) ?>"
                                <?= empty($day['is_working']) ? 'disabled' : '' ?>>
                        </td>
                        <td>
                            <input type="time" name="end_<?= $idx ?>" id="end_<?= $idx ?>"
                                value="<?= htmlspecialchars(substr($day['end_time'], 0, 5)) ?>"
                                <?= empty($day['is_working']) ? 'disabled' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('profile.save_schedule')) ?></button>
        <a href="/admin/employees" class="btn btn-outline"><?= htmlspecialchars($t('profile.cancel')) ?></a>
    </div>
</form>

<script>
function toggleDayRow(dayIdx, checked) {
    const startEl = document.getElementById('start_' + dayIdx);
    const endEl = document.getElementById('end_' + dayIdx);
    if (startEl) startEl.disabled = !checked;
    if (endEl) endEl.disabled = !checked;
}
</script>
