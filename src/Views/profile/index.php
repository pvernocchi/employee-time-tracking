<?php $title = $t('profile.title'); ?>

<div class="page-header">
    <h1>👤 <?= htmlspecialchars($t('profile.title')) ?></h1>
    <p><?= htmlspecialchars($t('profile.subtitle')) ?></p>
</div>

<!-- Preferences -->
<form method="POST" action="/profile/preferences">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <h2><?= htmlspecialchars($t('profile.preferences')) ?></h2>

        <div class="form-group">
            <label for="timezone"><?= htmlspecialchars($t('profile.timezone')) ?></label>
            <select id="timezone" name="timezone">
                <?php foreach ($timezones as $tz): ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= ($prefs['timezone'] ?? 'Europe/Madrid') === $tz ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tz) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="locale"><?= htmlspecialchars($t('profile.language')) ?></label>
            <select id="locale" name="locale">
                <?php foreach ($supportedLocales as $loc): ?>
                    <?php $meta = \App\Core\I18n::getLocaleMeta($loc); ?>
                    <option value="<?= htmlspecialchars($loc) ?>" <?= ($prefs['locale'] ?? 'es') === $loc ? 'selected' : '' ?>>
                        <?= htmlspecialchars($meta['abbr']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><?= htmlspecialchars($t('profile.theme')) ?></label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="theme" value="light"
                        <?= ($prefs['theme'] ?? 'light') === 'light' ? 'checked' : '' ?>>
                    ☀️ <?= htmlspecialchars($t('profile.theme_light')) ?>
                </label>
                <label class="radio-label">
                    <input type="radio" name="theme" value="dark"
                        <?= ($prefs['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
                    🌙 <?= htmlspecialchars($t('profile.theme_dark')) ?>
                </label>
            </div>
        </div>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('profile.save_preferences')) ?></button>
    </div>
</form>

<!-- Work Schedule -->
<form method="POST" action="/profile/schedule">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <h2>📅 <?= htmlspecialchars($t('profile.work_schedule')) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($t('profile.work_schedule_description')) ?></p>

        <table class="table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($t('profile.day')) ?></th>
                    <th><?= htmlspecialchars($t('profile.working')) ?></th>
                    <th><?= htmlspecialchars($t('profile.time_slots')) ?></th>
                    <th><?= htmlspecialchars($t('profile.daily_hours')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $idx => $dayKey): ?>
                    <?php $day = $schedule[$idx]; ?>
                    <?php
                    $daySlots = $day['slots'] ?? [];
                    if ($daySlots === []) {
                        $daySlots = [['start_time' => '09:00', 'end_time' => '17:00']];
                    }
                    ?>
                    <tr data-day="<?= (int) $idx ?>">
                        <td><?= htmlspecialchars($t('profile.day_' . $dayKey)) ?></td>
                        <td>
                            <input type="checkbox" name="working_<?= $idx ?>" value="1"
                                <?= !empty($day['is_working']) ? 'checked' : '' ?>
                                onchange="toggleDayRow(<?= $idx ?>, this.checked)">
                        </td>
                        <td>
                            <div class="time-slots" id="slots_<?= (int) $idx ?>">
                                <?php foreach ($daySlots as $slot): ?>
                                    <div class="form-row slot-row">
                                        <input type="time" name="start_<?= $idx ?>[]" value="<?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?>" <?= empty($day['is_working']) ? 'disabled' : '' ?>>
                                        <input type="time" name="end_<?= $idx ?>[]" value="<?= htmlspecialchars(substr($slot['end_time'], 0, 5)) ?>" <?= empty($day['is_working']) ? 'disabled' : '' ?>>
                                        <button type="button" class="btn btn-outline btn-sm" data-slot-remove="1" onclick="removeTimeSlot(this)" <?= empty($day['is_working']) ? 'disabled' : '' ?>>−</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm mt-1" data-slot-add="1" onclick="addTimeSlot(<?= (int) $idx ?>)" <?= empty($day['is_working']) ? 'disabled' : '' ?>>
                                + <?= htmlspecialchars($t('profile.add_time_slot')) ?>
                            </button>
                        </td>
                        <td id="daily_hours_<?= $idx ?>">0.00 h</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3"><?= htmlspecialchars($t('profile.weekly_hours')) ?></th>
                    <th id="weekly_hours_total">0.00 h</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('profile.save_schedule')) ?></button>
    </div>
</form>

<script>
function createTimeSlotRow(dayIdx, startValue = '', endValue = '', disabled = false) {
    const row = document.createElement('div');
    row.className = 'form-row slot-row';

    const startEl = document.createElement('input');
    startEl.type = 'time';
    startEl.name = `start_${dayIdx}[]`;
    startEl.value = startValue;
    startEl.disabled = disabled;

    const endEl = document.createElement('input');
    endEl.type = 'time';
    endEl.name = `end_${dayIdx}[]`;
    endEl.value = endValue;
    endEl.disabled = disabled;

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-outline btn-sm';
    removeBtn.textContent = '−';
    removeBtn.setAttribute('data-slot-remove', '1');
    removeBtn.disabled = disabled;
    removeBtn.addEventListener('click', () => removeTimeSlot(removeBtn));

    row.appendChild(startEl);
    row.appendChild(endEl);
    row.appendChild(removeBtn);
    return row;
}

function getDayRow(dayIdx) {
    return document.querySelector(`tr[data-day="${dayIdx}"]`);
}

function updateDaySlotButtons(dayIdx) {
    const row = getDayRow(dayIdx);
    if (!row) return;
    const removeButtons = row.querySelectorAll('button[data-slot-remove="1"]');
    const working = row.querySelector(`input[name="working_${dayIdx}"]`);
    const disableRemove = removeButtons.length <= 1 || !(working && working.checked);
    removeButtons.forEach((btn) => {
        btn.disabled = disableRemove;
    });
}

function addTimeSlot(dayIdx) {
    const row = getDayRow(dayIdx);
    const slotsEl = document.getElementById(`slots_${dayIdx}`);
    if (!row || !slotsEl) return;
    const working = row.querySelector(`input[name="working_${dayIdx}"]`);
    const disabled = !(working && working.checked);
    slotsEl.appendChild(createTimeSlotRow(dayIdx, '', '', disabled));
    updateDaySlotButtons(dayIdx);
    updateScheduleTotals();
}

function removeTimeSlot(button) {
    const row = button.closest('tr[data-day]');
    if (!row) return;
    const dayIdx = Number(row.getAttribute('data-day'));
    const slots = row.querySelectorAll('.slot-row');
    if (slots.length <= 1) return;
    const slotRow = button.closest('.slot-row');
    if (slotRow) slotRow.remove();
    updateDaySlotButtons(dayIdx);
    updateScheduleTotals();
}

function toggleDayRow(dayIdx, checked) {
    const row = getDayRow(dayIdx);
    if (!row) return;
    row.querySelectorAll(`input[name="start_${dayIdx}[]"], input[name="end_${dayIdx}[]"], button[data-slot-remove="1"], button[data-slot-add="1"]`).forEach((el) => {
        el.disabled = !checked;
    });
    updateDaySlotButtons(dayIdx);
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
        const hoursEl = document.getElementById(`daily_hours_${dayIdx}`);
        let dayMinutes = 0;

        if (working && working.checked) {
            const starts = document.querySelectorAll(`input[name="start_${dayIdx}[]"]`);
            const ends = document.querySelectorAll(`input[name="end_${dayIdx}[]"]`);
            const slotsCount = Math.min(starts.length, ends.length);
            for (let i = 0; i < slotsCount; i++) {
                const start = parseTimeToMinutes(starts[i].value);
                const end = parseTimeToMinutes(ends[i].value);
                if (start !== null && end !== null && end > start) {
                    dayMinutes += end - start;
                }
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
        updateDaySlotButtons(dayIdx);
        const working = document.querySelector(`input[name="working_${dayIdx}"]`);
        if (working) working.addEventListener('change', updateScheduleTotals);
    }
    document.addEventListener('input', (event) => {
        if (event.target && (event.target.matches('input[type="time"]'))) {
            updateScheduleTotals();
        }
    });
    updateScheduleTotals();
});
</script>

<!-- Notification Preferences -->
<form method="POST" action="/profile/notifications">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <h2>🔔 <?= htmlspecialchars($t('notifications.preferences_title')) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($t('notifications.preferences_description')) ?></p>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="clock_in_reminder" value="1"
                    <?= !empty($notifPrefs['clock_in_reminder']) ? 'checked' : '' ?>>
                <?= htmlspecialchars($t('notifications.clock_in_reminder')) ?>
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="clock_out_reminder" value="1"
                    <?= !empty($notifPrefs['clock_out_reminder']) ? 'checked' : '' ?>>
                <?= htmlspecialchars($t('notifications.clock_out_reminder')) ?>
            </label>
        </div>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('notifications.save_preferences')) ?></button>
    </div>
</form>

<!-- Change Password -->
<form method="POST" action="/profile/password">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <h2>🔑 <?= htmlspecialchars($t('profile.change_password')) ?></h2>

        <div class="form-group">
            <label for="current_password"><?= htmlspecialchars($t('profile.current_password')) ?></label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="new_password"><?= htmlspecialchars($t('profile.new_password')) ?></label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password"><?= htmlspecialchars($t('profile.confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
        </div>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('profile.change_password_button')) ?></button>
    </div>
</form>
