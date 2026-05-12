<?php
$title = $pageTitle ?? 'Request Leave';
$request = $request ?? [
    'leave_type' => '',
    'start_date' => '',
    'end_date' => '',
    'reason' => '',
    'status' => 'pending',
];
$formAction = $formAction ?? '/leave/request';
$submitLabel = $submitLabel ?? 'Submit Request';
?>

<div class="page-header">
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="page-actions">
        <a href="/leave" class="btn btn-outline">← Back to Leave</a>
    </div>
</div>

<div class="card">
    <?php if (($request['status'] ?? '') === 'approved'): ?>
        <div class="alert alert-warning">Approved requests will be sent for review again after editing.</div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="leave_type">Leave Type</label>
            <select name="leave_type" id="leave_type" required>
                <option value="">Select type...</option>
                <option value="vacation" <?= $request['leave_type'] === 'vacation' ? 'selected' : '' ?>>Vacaciones / Vacation (22 días - Art. 38 ET)</option>
                <option value="sick" <?= $request['leave_type'] === 'sick' ? 'selected' : '' ?>>Baja por enfermedad / Sick Leave</option>
                <option value="personal" <?= $request['leave_type'] === 'personal' ? 'selected' : '' ?>>Asuntos propios / Personal</option>
                <option value="unpaid" <?= $request['leave_type'] === 'unpaid' ? 'selected' : '' ?>>Sin sueldo / Unpaid</option>
                <option value="maternity" <?= $request['leave_type'] === 'maternity' ? 'selected' : '' ?>>Maternidad / Maternity (16 semanas - Art. 48.4 ET)</option>
                <option value="paternity" <?= $request['leave_type'] === 'paternity' ? 'selected' : '' ?>>Paternidad / Paternity (16 semanas - Art. 48.4 ET)</option>
                <option value="marriage" <?= $request['leave_type'] === 'marriage' ? 'selected' : '' ?>>Matrimonio / Marriage (15 días - Art. 37.3 ET)</option>
                <option value="bereavement" <?= $request['leave_type'] === 'bereavement' ? 'selected' : '' ?>>Fallecimiento familiar / Bereavement (2-4 días - Art. 37.3 ET)</option>
                <option value="moving" <?= $request['leave_type'] === 'moving' ? 'selected' : '' ?>>Mudanza / Moving (1 día - Art. 37.3 ET)</option>
                <option value="jury_duty" <?= $request['leave_type'] === 'jury_duty' ? 'selected' : '' ?>>Deber público / Jury Duty (Art. 37.3 ET)</option>
                <option value="other" <?= $request['leave_type'] === 'other' ? 'selected' : '' ?>>Otro / Other</option>
            </select>
        </div>

        <div class="card" style="background: #f8f9fa; padding: 1rem; margin-bottom: 1rem;">
            <small><strong>Días legales según Estatuto de los Trabajadores:</strong></small>
            <ul style="font-size: 0.85rem; margin: 0.5rem 0 0 1rem;">
                <li>Vacaciones: 22 días laborables / 30 días naturales mínimo</li>
                <li>Matrimonio: 15 días naturales</li>
                <li>Fallecimiento/enfermedad grave familiar: 2 días (4 si desplazamiento)</li>
                <li>Mudanza: 1 día</li>
                <li>Maternidad/Paternidad: 16 semanas</li>
                <li>Deber público inexcusable: el tiempo indispensable</li>
            </ul>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" required value="<?= htmlspecialchars($request['start_date']) ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" required value="<?= htmlspecialchars($request['end_date']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <textarea name="reason" id="reason" rows="3" placeholder="Brief description..."><?= htmlspecialchars($request['reason'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($submitLabel) ?></button>
            <a href="/leave" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
