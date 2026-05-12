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
