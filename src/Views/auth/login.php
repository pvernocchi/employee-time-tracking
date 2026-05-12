<div class="auth-card">
    <div class="auth-header">
        <h1>⏱️ <?= htmlspecialchars($t('app.short_name')) ?></h1>
        <p><?= htmlspecialchars($t('auth.sign_in')) ?></p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="/login" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label for="email"><?= htmlspecialchars($t('auth.email_address')) ?></label>
            <input type="email" id="email" name="email" required autofocus placeholder="you@company.com">
        </div>

        <div class="form-group">
            <label for="password"><?= htmlspecialchars($t('auth.password')) ?></label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t('auth.sign_in_button')) ?></button>
    </form>
</div>
