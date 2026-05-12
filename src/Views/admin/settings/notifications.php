<?php $title = $t('notifications.admin_title'); ?>

<div class="page-header">
    <h1>🔔 <?= htmlspecialchars($t('notifications.admin_title')) ?></h1>
    <p class="text-muted"><?= htmlspecialchars($t('notifications.admin_description')) ?></p>
</div>

<div class="card">
    <form method="POST" action="/admin/settings/notifications">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <h2><?= htmlspecialchars($t('notifications.available_notifications')) ?></h2>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="clock_in_reminder" value="1"
                       <?= ($settings['clock_in_reminder'] ?? '0') === '1' ? 'checked' : '' ?>>
                <?= htmlspecialchars($t('notifications.clock_in_reminder')) ?>
            </label>
            <p class="text-muted" style="margin-left:1.5rem;margin-top:0.25rem;">
                <?= htmlspecialchars($t('notifications.clock_in_reminder_desc')) ?>
            </p>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="clock_out_reminder" value="1"
                       <?= ($settings['clock_out_reminder'] ?? '0') === '1' ? 'checked' : '' ?>>
                <?= htmlspecialchars($t('notifications.clock_out_reminder')) ?>
            </label>
            <p class="text-muted" style="margin-left:1.5rem;margin-top:0.25rem;">
                <?= htmlspecialchars($t('notifications.clock_out_reminder_desc')) ?>
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('notifications.save_settings')) ?></button>
        </div>
    </form>
</div>
