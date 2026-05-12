<div class="auth-card" style="max-width: 500px;">
    <div class="auth-header">
        <h1>📱 Set Up Authenticator App</h1>
        <p>Scan the QR code with your authenticator app (Microsoft Authenticator, Google Authenticator, Authy, etc.).</p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- QR Code (generated client-side) -->
    <div style="text-align:center; margin: 1.5rem 0;">
        <canvas id="qr-canvas"></canvas>
    </div>

    <div class="form-group" style="text-align:center;">
        <p class="text-muted" style="font-size:.875rem; margin-bottom:.25rem;">
            Or enter this key manually:
        </p>
        <code id="secret-display" style="font-size:1rem; letter-spacing:.2em; word-break:break-all;">
            <?= htmlspecialchars($secret) ?>
        </code>
    </div>

    <form method="POST" action="/mfa/enroll/totp" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="device_name">Device Name</label>
            <input type="text" id="device_name" name="device_name"
                value="Authenticator App" maxlength="100">
        </div>

        <div class="form-group">
            <label for="totp_code">Enter the 6-digit code from your app to confirm</label>
            <input type="text" id="totp_code" name="totp_code"
                inputmode="numeric" pattern="\d{6}" maxlength="6"
                required autofocus autocomplete="one-time-code"
                placeholder="123456">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Confirm &amp; Enable</button>
    </form>

    <div style="margin-top: 1rem; text-align: center;">
        <a href="/mfa/setup" style="font-size:.875rem;">← Back</a>
    </div>
</div>

<script>
// Generate QR code using qrcodejs from CDN
(function() {
    var script  = document.createElement('script');
    script.src  = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    script.onload = function() {
        var canvas  = document.getElementById('qr-canvas');
        new QRCode(canvas, {
            text:   <?= json_encode($otpUri) ?>,
            width:  200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    };
    document.head.appendChild(script);
})();
</script>
