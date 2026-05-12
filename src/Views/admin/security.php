<?php $title = 'Security Settings'; ?>

<div class="page-header">
    <h1>🔒 Security Settings</h1>
    <p>Configure multi-factor authentication and CAPTCHA policies.</p>
</div>

<form method="POST" action="/admin/security">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- ------------------------------------------------------------------ -->
    <!-- MFA Policy                                                          -->
    <!-- ------------------------------------------------------------------ -->
    <div class="card mt-2">
        <h2>Multi-Factor Authentication (MFA)</h2>

        <div class="form-group">
            <label>Global MFA Policy</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="mfa_policy" value="optional"
                        <?= ($settings['mfa_policy'] ?? 'optional') === 'optional' ? 'checked' : '' ?>>
                    <strong>Optional</strong> – users may enrol an MFA method, but it is not required.
                </label>
                <label class="radio-label">
                    <input type="radio" name="mfa_policy" value="mandatory"
                        <?= ($settings['mfa_policy'] ?? 'optional') === 'mandatory' ? 'checked' : '' ?>>
                    <strong>Mandatory</strong> – all users must enrol at least one MFA method before accessing the app.
                </label>
            </div>
        </div>

        <p class="text-muted" style="font-size:.875rem;">
            The per-user override (visible on each employee's MFA page) can force MFA individually
            even when the global policy is <em>Optional</em>.
        </p>
    </div>

    <!-- ------------------------------------------------------------------ -->
    <!-- CAPTCHA                                                             -->
    <!-- ------------------------------------------------------------------ -->
    <div class="card mt-2">
        <h2>CAPTCHA on Sign-In Page</h2>

        <div class="form-group">
            <label>CAPTCHA Provider</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="captcha_provider" value="none"
                        <?= ($settings['captcha_provider'] ?? 'none') === 'none' ? 'checked' : '' ?>>
                    <strong>None</strong> – no CAPTCHA.
                </label>
                <label class="radio-label">
                    <input type="radio" name="captcha_provider" value="cloudflare"
                        <?= ($settings['captcha_provider'] ?? 'none') === 'cloudflare' ? 'checked' : '' ?>>
                    <strong>Cloudflare Turnstile</strong>
                </label>
                <label class="radio-label">
                    <input type="radio" name="captcha_provider" value="recaptcha"
                        <?= ($settings['captcha_provider'] ?? 'none') === 'recaptcha' ? 'checked' : '' ?>>
                    <strong>Google reCAPTCHA</strong>
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="captcha_site_key">Site Key (public)</label>
                <input type="text" id="captcha_site_key" name="captcha_site_key"
                    value="<?= htmlspecialchars($settings['captcha_site_key'] ?? '') ?>"
                    placeholder="Your CAPTCHA site key">
            </div>
            <div class="form-group">
                <label for="captcha_secret_key">Secret Key (private)</label>
                <input type="password" id="captcha_secret_key" name="captcha_secret_key"
                    value="<?= htmlspecialchars($settings['captcha_secret_key'] ?? '') ?>"
                    placeholder="Your CAPTCHA secret key">
            </div>
        </div>

        <div class="form-group" id="recaptcha_version_group" style="<?= ($settings['captcha_provider'] ?? 'none') !== 'recaptcha' ? 'display:none' : '' ?>">
            <label>reCAPTCHA Version</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="captcha_recaptcha_version" value="v2"
                        <?= ($settings['captcha_recaptcha_version'] ?? 'v2') === 'v2' ? 'checked' : '' ?>>
                    v2 – "I'm not a robot" checkbox
                </label>
                <label class="radio-label">
                    <input type="radio" name="captcha_recaptcha_version" value="v3"
                        <?= ($settings['captcha_recaptcha_version'] ?? 'v2') === 'v3' ? 'checked' : '' ?>>
                    v3 – invisible / score-based (score ≥ 0.5 required)
                </label>
            </div>
        </div>
    </div>

    <div class="form-actions mt-2">
        <button type="submit" class="btn btn-primary">Save Security Settings</button>
    </div>
</form>

<script>
document.querySelectorAll('input[name="captcha_provider"]').forEach(function(el) {
    el.addEventListener('change', function() {
        document.getElementById('recaptcha_version_group').style.display =
            (this.value === 'recaptcha') ? '' : 'none';
    });
});
</script>
