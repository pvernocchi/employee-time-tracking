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
                    <th><?= htmlspecialchars($t('profile.daily_hours')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $idx => $dayKey): ?>
                    <?php $day = $schedule[$idx]; ?>
                    <tr data-day="<?= (int) $idx ?>">
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
                        <td id="daily_hours_<?= $idx ?>">0.00 h</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4"><?= htmlspecialchars($t('profile.weekly_hours')) ?></th>
                    <th id="weekly_hours_total">0.00 h</th>
                </tr>
            </tfoot>
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
    updateScheduleTotals();
}

function parseTimeToMinutes(value) {
    if (!/^\d{2}:\d{2}$/.test(value)) return null;
    const [hours, minutes] = value.split(':').map(Number);
    if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) return null;
    return (hours * 60) + minutes;
}

function updateScheduleTotals() {
    let totalMinutes = 0;

    for (let dayIdx = 0; dayIdx < 7; dayIdx++) {
        const working = document.querySelector(`input[name="working_${dayIdx}"]`);
        const startEl = document.getElementById(`start_${dayIdx}`);
        const endEl = document.getElementById(`end_${dayIdx}`);
        const hoursEl = document.getElementById(`daily_hours_${dayIdx}`);
        let dayMinutes = 0;

        if (working && working.checked && startEl && endEl) {
            const start = parseTimeToMinutes(startEl.value);
            const end = parseTimeToMinutes(endEl.value);
            if (start !== null && end !== null && end > start) {
                dayMinutes = end - start;
            }
        }

        totalMinutes += dayMinutes;
        if (hoursEl) {
            hoursEl.textContent = `${(dayMinutes / 60).toFixed(2)} h`;
        }
    }

    const weeklyEl = document.getElementById('weekly_hours_total');
    if (weeklyEl) {
        weeklyEl.textContent = `${(totalMinutes / 60).toFixed(2)} h`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    for (let dayIdx = 0; dayIdx < 7; dayIdx++) {
        const startEl = document.getElementById(`start_${dayIdx}`);
        const endEl = document.getElementById(`end_${dayIdx}`);
        if (startEl) startEl.addEventListener('input', updateScheduleTotals);
        if (endEl) endEl.addEventListener('input', updateScheduleTotals);
        const working = document.querySelector(`input[name="working_${dayIdx}"]`);
        if (working) working.addEventListener('change', updateScheduleTotals);
    }
    updateScheduleTotals();
});
</script>
