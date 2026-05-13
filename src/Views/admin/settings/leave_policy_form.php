<?php
$isEdit = $policy !== null;
$isStatutory = $isEdit && (bool) $policy['is_statutory'];
$title = $isEdit ? $t('leave_policy.edit_title') : $t('leave_policy.create_title');
$formAction = $isEdit
    ? '/admin/settings/leave-policy/' . (int) $policy['id']
    : '/admin/settings/leave-policy';
?>

<div class="page-header">
    <h1><?= htmlspecialchars($isEdit ? $t('leave_policy.edit_heading') : $t('leave_policy.create_heading')) ?></h1>
    <div class="page-actions">
        <a href="/admin/settings/leave-policy" class="btn btn-outline"><?= htmlspecialchars($t('leave_policy.back')) ?></a>
    </div>
</div>

<div class="card">
    <?php if ($isStatutory): ?>
    <div class="alert alert-warning" style="margin-bottom:1.5rem;">
        <strong><?= htmlspecialchars($t('leave_policy.statutory_alert_title')) ?></strong><br>
        <?= htmlspecialchars($t('leave_policy.statutory_alert_body')) ?>
        <?php if ((float) $policy['min_statutory_days'] > 0): ?>
            <br><small><?= htmlspecialchars($t('leave_policy.minimum_legal_days', ['days' => rtrim(rtrim(number_format((float) $policy['min_statutory_days'], 1), '0'), '.')])) ?></small>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label for="name"><?= htmlspecialchars($t('leave_policy.category_name')) ?><?= !$isStatutory ? ' *' : '' ?></label>
            <?php if ($isStatutory): ?>
                <input type="text" id="name" value="<?= htmlspecialchars($policy['name']) ?>" disabled>
                <small class="text-muted"><?= htmlspecialchars($t('leave_policy.statutory_name_locked')) ?></small>
            <?php else: ?>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($policy['name'] ?? '') ?>"
                       placeholder="<?= htmlspecialchars($t('leave_policy.category_placeholder')) ?>">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="legal_days"><?= htmlspecialchars($t('leave_policy.legal_days_label')) ?></label>
            <input type="number" id="legal_days" name="legal_days"
                   min="<?= $isStatutory ? (float) $policy['min_statutory_days'] : '0' ?>"
                   step="0.5"
                   value="<?= $isEdit ? htmlspecialchars((string) (float) $policy['legal_days']) : '0' ?>"
                   placeholder="0">
            <?php if ($isStatutory && (float) $policy['min_statutory_days'] > 0): ?>
                <small class="text-muted">
                    <?= htmlspecialchars($t('leave_policy.minimum_help', ['days' => rtrim(rtrim(number_format((float) $policy['min_statutory_days'], 1), '0'), '.')])) ?>
                </small>
            <?php else: ?>
                <small class="text-muted"><?= htmlspecialchars($t('leave_policy.zero_help')) ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="dec_24_31_deduction"><?= htmlspecialchars($t('leave_policy.december_deduction_label')) ?></label>
            <select id="dec_24_31_deduction" name="dec_24_31_deduction">
                <?php $decemberDeduction = $isEdit ? ($policy['dec_24_31_deduction'] ?? 'full') : 'full'; ?>
                <option value="full" <?= $decemberDeduction === 'full' ? 'selected' : '' ?>><?= htmlspecialchars($t('leave_policy.full_day')) ?></option>
                <option value="half" <?= $decemberDeduction === 'half' ? 'selected' : '' ?>><?= htmlspecialchars($t('leave_policy.half_day')) ?></option>
            </select>
            <small class="text-muted">
                <?= htmlspecialchars($t('leave_policy.december_deduction_help')) ?>
            </small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <?php if ($isStatutory): ?>
                    <input type="checkbox" checked disabled>
                <?php else: ?>
                    <input type="checkbox" name="is_statutory" id="is_statutory" value="1"
                           <?= ($isEdit && $policy['is_statutory']) ? 'checked' : '' ?>>
                <?php endif; ?>
                <?= htmlspecialchars($t('leave_policy.is_statutory_label')) ?>
            </label>
            <small class="text-muted">
                <?= htmlspecialchars($t('leave_policy.is_statutory_help')) ?>
            </small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="tracks_balance" id="tracks_balance" value="1"
                       <?= (!$isEdit || $policy['tracks_balance']) ? 'checked' : '' ?>>
                <?= htmlspecialchars($t('leave_policy.tracks_balance_label')) ?>
            </label>
            <small class="text-muted">
                <?= htmlspecialchars($t('leave_policy.tracks_balance_help')) ?>
            </small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= htmlspecialchars($isEdit ? $t('leave_policy.save_changes') : $t('leave_policy.create_category')) ?>
            </button>
            <a href="/admin/settings/leave-policy" class="btn btn-outline"><?= htmlspecialchars($t('leave_policy.cancel')) ?></a>
        </div>
    </form>
</div>
