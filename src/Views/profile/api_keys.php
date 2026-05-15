<?php $title = $t('api_keys.page_title'); ?>

<div class="page-header">
    <h1>🗝️ <?= htmlspecialchars($t('api_keys.title')) ?></h1>
    <p><?= htmlspecialchars($t('api_keys.subtitle')) ?></p>
</div>

<?php if (!empty($newApiKey)): ?>
    <div class="alert alert-success">
        <strong><?= htmlspecialchars($t('api_keys.new_key_visible_once')) ?></strong><br>
        <?= htmlspecialchars($t('api_keys.copy_now_notice')) ?>
        <div style="margin-top:.75rem;">
            <code><?= htmlspecialchars($newApiKey) ?></code>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="/profile/api-keys">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="card mt-2">
        <h2><?= htmlspecialchars($t('api_keys.create_heading')) ?></h2>

        <div class="form-row">
            <div class="form-group">
                <label for="name"><?= htmlspecialchars($t('api_keys.name')) ?></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    maxlength="100"
                    placeholder="<?= htmlspecialchars($t('api_keys.name_placeholder')) ?>"
                >
            </div>

            <div class="form-group">
                <label for="expires_at"><?= htmlspecialchars($t('api_keys.expires_at')) ?></label>
                <input type="datetime-local" id="expires_at" name="expires_at">
                <p class="text-muted" style="margin-top:.5rem;"><?= htmlspecialchars($t('api_keys.expires_at_help')) ?></p>
            </div>
        </div>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('api_keys.create_button')) ?></button>
    </div>
</form>

<div class="card mt-2">
    <h2><?= htmlspecialchars($t('api_keys.list_heading')) ?> (<?= count($apiKeys) ?>)</h2>

    <?php if (empty($apiKeys)): ?>
        <p class="text-muted"><?= htmlspecialchars($t('api_keys.empty')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($t('api_keys.name')) ?></th>
                    <th><?= htmlspecialchars($t('api_keys.status')) ?></th>
                    <th><?= htmlspecialchars($t('api_keys.created_at')) ?></th>
                    <th><?= htmlspecialchars($t('api_keys.last_used_at')) ?></th>
                    <th><?= htmlspecialchars($t('api_keys.expires_at')) ?></th>
                    <th><?= htmlspecialchars($t('api_keys.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apiKeys as $key): ?>
                    <?php
                    $isExpired = !empty($key['expires_at']) && strtotime($key['expires_at']) <= time();
                    $isActive = !empty($key['is_active']) && !$isExpired;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $key['name']) ?></td>
                        <td>
                            <span class="badge <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                <?= htmlspecialchars($isActive ? $t('api_keys.status_active') : ($isExpired ? $t('api_keys.status_expired') : $t('api_keys.status_inactive'))) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $key['created_at']))) ?></td>
                        <td>
                            <?= !empty($key['last_used_at'])
                                ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $key['last_used_at'])))
                                : '—' ?>
                        </td>
                        <td>
                            <?= !empty($key['expires_at'])
                                ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $key['expires_at'])))
                                : '—' ?>
                        </td>
                        <td>
                            <?php if (!empty($key['is_active'])): ?>
                                <form method="POST" action="/profile/api-keys/<?= (int) $key['id'] ?>/revoke" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline"
                                        onclick="return confirm('<?= htmlspecialchars($t('api_keys.revoke_confirm')) ?>')"
                                    >
                                        <?= htmlspecialchars($t('api_keys.revoke_button')) ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
