<div class="auth-card">
    <div class="auth-header">
        <h1>⏱️ <?= htmlspecialchars($t('app.short_name')) ?></h1>
        <p><?= htmlspecialchars($t('auth.sign_in')) ?></p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form id="login-form" method="POST" action="/login" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label for="email"><?= htmlspecialchars($t('auth.email_address')) ?></label>
            <input type="email" id="email" name="email" required autofocus placeholder="you@company.com">
        </div>

        <div class="form-group">
            <label for="password"><?= htmlspecialchars($t('auth.password')) ?></label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>

        <?php if (($captchaProvider ?? 'none') === 'cloudflare' && !empty($captchaSiteKey)): ?>
        <div class="form-group">
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($captchaSiteKey) ?>"></div>
        </div>
        <?php elseif (($captchaProvider ?? 'none') === 'recaptcha' && !empty($captchaSiteKey)): ?>
            <?php if (($recaptchaVersion ?? 'v2') === 'v2'): ?>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($captchaSiteKey) ?>"></div>
            </div>
            <?php else: ?>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t('auth.sign_in_button')) ?></button>
    </form>
</div>

<?php if (($captchaProvider ?? 'none') === 'cloudflare' && !empty($captchaSiteKey)): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php elseif (($captchaProvider ?? 'none') === 'recaptcha' && !empty($captchaSiteKey)): ?>
    <?php if (($recaptchaVersion ?? 'v2') === 'v2'): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php else: ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($captchaSiteKey) ?>"></script>
<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    grecaptcha.ready(function() {
        grecaptcha.execute(<?= json_encode($captchaSiteKey) ?>, {action: 'login'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
            form.submit();
        });
    });
});
</script>
    <?php endif; ?>
<?php endif; ?>

