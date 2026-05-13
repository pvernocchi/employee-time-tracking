<?php
$title = $pageTitle ?? $t('leave.request.page_title');
$request = $request ?? [
    'leave_type' => '',
    'start_date' => '',
    'end_date' => '',
    'reason' => '',
    'status' => 'pending',
];
$formAction = $formAction ?? '/leave/request';
$submitLabel = $submitLabel ?? $t('leave.request.submit');
$leaveTypeOptions = [
    'vacation' => 'leave.request.option.vacation',
    'sick' => 'leave.request.option.sick',
    'personal' => 'leave.request.option.personal',
    'unpaid' => 'leave.request.option.unpaid',
    'maternity' => 'leave.request.option.maternity',
    'paternity' => 'leave.request.option.paternity',
    'marriage' => 'leave.request.option.marriage',
    'bereavement' => 'leave.request.option.bereavement',
    'moving' => 'leave.request.option.moving',
    'jury_duty' => 'leave.request.option.jury_duty',
    'other' => 'leave.request.option.other',
];
$legalDays = [
    'leave.request.legal_days_vacation',
    'leave.request.legal_days_marriage',
    'leave.request.legal_days_bereavement',
    'leave.request.legal_days_moving',
    'leave.request.legal_days_parental',
    'leave.request.legal_days_jury',
];
?>

<div class="page-header">
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="page-actions">
        <a href="/leave" class="btn btn-outline"><?= htmlspecialchars($t('leave.request.back')) ?></a>
    </div>
</div>

<div class="card">
    <?php if (($request['status'] ?? '') === 'approved'): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($t('leave.request.approved_warning')) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="leave_type"><?= htmlspecialchars($t('leave.request.leave_type')) ?></label>
            <select name="leave_type" id="leave_type" required>
                <option value=""><?= htmlspecialchars($t('leave.request.select_type')) ?></option>
                <?php foreach ($leaveTypeOptions as $value => $translationKey): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $request['leave_type'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($t($translationKey)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card" style="background: #f8f9fa; padding: 1rem; margin-bottom: 1rem;">
            <small><strong><?= htmlspecialchars($t('leave.request.legal_days_notice')) ?></strong></small>
            <ul style="font-size: 0.85rem; margin: 0.5rem 0 0 1rem;">
                <?php foreach ($legalDays as $translationKey): ?>
                    <li><?= htmlspecialchars($t($translationKey)) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date"><?= htmlspecialchars($t('leave.request.start_date')) ?></label>
                <input type="date" name="start_date" id="start_date" required value="<?= htmlspecialchars($request['start_date']) ?>">
            </div>
            <div class="form-group">
                <label for="end_date"><?= htmlspecialchars($t('leave.request.end_date')) ?></label>
                <input type="date" name="end_date" id="end_date" required value="<?= htmlspecialchars($request['end_date']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="reason"><?= htmlspecialchars($t('leave.request.reason')) ?></label>
            <textarea name="reason" id="reason" rows="3" placeholder="<?= htmlspecialchars($t('leave.request.reason_placeholder')) ?>"><?= htmlspecialchars($request['reason'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($submitLabel) ?></button>
            <a href="/leave" class="btn btn-outline"><?= htmlspecialchars($t('leave.request.cancel')) ?></a>
        </div>
    </form>
</div>
