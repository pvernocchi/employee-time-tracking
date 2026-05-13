<?php $title = $t('leave_policy.title'); ?>

<div class="page-header">
    <h1>📋 <?= htmlspecialchars($t('leave_policy.heading')) ?></h1>
    <div class="page-actions">
        <a href="/admin/settings/leave-policy/create" class="btn btn-primary"><?= htmlspecialchars($t('leave_policy.new_category')) ?></a>
        <a href="/admin/settings/smtp" class="btn btn-outline"><?= htmlspecialchars($t('leave_policy.back_to_settings')) ?></a>
    </div>
</div>

<div class="card">
    <p class="text-muted">
        <?= htmlspecialchars($t('leave_policy.description')) ?>
        <?= htmlspecialchars($t('leave_policy.description_statutory')) ?>
        <?= htmlspecialchars($t('leave_policy.description_days')) ?>
    </p>

    <?php if (empty($policies)): ?>
        <p class="text-muted"><?= htmlspecialchars($t('leave_policy.empty')) ?></p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th><?= htmlspecialchars($t('leave_policy.category')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.legal_days')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.december_deduction')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.tracks_balance')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.statutory')) ?></th>
                <th><?= htmlspecialchars($t('leave_policy.actions')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($policies as $policy): ?>
            <tr>
                <td><?= htmlspecialchars($policy['name']) ?></td>
                <td>
                    <?php
                    $days = (float) $policy['legal_days'];
                    echo $days > 0 ? rtrim(rtrim(number_format($days, 1), '0'), '.') : '—';
                    ?>
                </td>
                <td><?= htmlspecialchars(($policy['dec_24_31_deduction'] ?? 'full') === 'half' ? $t('leave_policy.half_day') : $t('leave_policy.full_day')) ?></td>
                <td>
                    <?php if ($policy['tracks_balance'] ?? true): ?>
                        <span class="badge badge-active"><?= htmlspecialchars($t('leave_policy.yes')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-inactive"><?= htmlspecialchars($t('leave_policy.not_applicable')) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($policy['is_statutory']): ?>
                        <span class="badge badge-active" title="<?= htmlspecialchars($t('leave_policy.statute_title')) ?>">⚖️ <?= htmlspecialchars($t('leave_policy.yes')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-inactive"><?= htmlspecialchars($t('leave_policy.no')) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/admin/settings/leave-policy/<?= (int) $policy['id'] ?>/edit" class="btn btn-sm btn-outline"><?= htmlspecialchars($t('leave_policy.edit')) ?></a>
                    <?php if (!$policy['is_statutory']): ?>
                    <form method="POST" action="/admin/settings/leave-policy/<?= (int) $policy['id'] ?>/delete"
                          style="display:inline-block;"
                          onsubmit="return confirm('<?= htmlspecialchars($t('leave_policy.delete_confirm'), ENT_QUOTES) ?>');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><?= htmlspecialchars($t('leave_policy.delete')) ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
