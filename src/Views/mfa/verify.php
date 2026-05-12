<div class="auth-card">
    <div class="auth-header">
        <h1>🔐 Two-Factor Verification</h1>
        <p>Verify your identity to continue.</p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php
    $hasTOTP    = false;
    $hasWebAuthn = false;
    foreach ($methods as $m) {
        if ($m['type'] === 'totp')    $hasTOTP    = true;
        if ($m['type'] === 'webauthn') $hasWebAuthn = true;
    }
    ?>

    <?php if ($hasTOTP): ?>
    <form method="POST" action="/mfa/verify" class="auth-form" id="totp-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="mfa_method" value="totp">

        <div class="form-group">
            <label for="totp_code">6-digit code from your authenticator app</label>
            <input type="text" id="totp_code" name="totp_code"
                inputmode="numeric" pattern="\d{6}" maxlength="6"
                required autofocus autocomplete="one-time-code"
                placeholder="123456">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Verify Code</button>
    </form>
    <?php endif; ?>

    <?php if ($hasWebAuthn): ?>
    <div style="margin-top: 1rem; text-align: center;">
        <?php if ($hasTOTP): ?><p class="text-muted" style="margin: .75rem 0;">— or —</p><?php endif; ?>
        <button type="button" class="btn btn-outline btn-block" id="webauthn-btn" onclick="startWebAuthnAuth()">
            🔑 Use Security Key / Passkey
        </button>
        <p id="webauthn-status" class="text-muted" style="margin-top:.5rem; display:none;"></p>
    </div>
    <?php endif; ?>

    <div style="margin-top: 1.5rem; text-align: center;">
        <a href="/logout" class="text-muted" style="font-size:.875rem;">Cancel and sign out</a>
    </div>
</div>

<?php if ($hasWebAuthn): ?>
<script>
async function startWebAuthnAuth() {
    const btn    = document.getElementById('webauthn-btn');
    const status = document.getElementById('webauthn-status');

    btn.disabled = true;
    status.style.display = '';
    status.textContent = 'Requesting challenge…';

    try {
        // 1. Get challenge from server
        const challengeRes = await fetch('/mfa/webauthn/auth-challenge', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({csrf_token: '<?= $_SESSION['csrf_token'] ?>'})
        });
        const challengeData = await challengeRes.json();

        if (challengeData.error) throw new Error(challengeData.error);

        // 2. Convert base64url to ArrayBuffer helpers
        const b64ToAb = s => Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
        const abToB64 = ab => btoa(String.fromCharCode(...new Uint8Array(ab)));

        // 3. Prepare publicKey options
        const publicKey = challengeData.publicKey;
        publicKey.challenge = b64ToAb(publicKey.challenge);
        if (publicKey.allowCredentials) {
            publicKey.allowCredentials = publicKey.allowCredentials.map(c => ({
                ...c, id: b64ToAb(c.id)
            }));
        }

        // 4. Get credential
        status.textContent = 'Waiting for security key…';
        const credential = await navigator.credentials.get({publicKey});

        // 5. Send to server
        status.textContent = 'Verifying…';
        const verifyRes = await fetch('/mfa/webauthn/auth-verify', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id:                credential.rawId ? abToB64(credential.rawId) : credential.id,
                rawId:             abToB64(credential.rawId),
                clientDataJSON:    abToB64(credential.response.clientDataJSON),
                authenticatorData: abToB64(credential.response.authenticatorData),
                signature:         abToB64(credential.response.signature),
                signCount:         credential.response.authenticatorData
                                   ? new DataView(credential.response.authenticatorData).getUint32(33) : 0,
            })
        });
        const verifyData = await verifyRes.json();

        if (verifyData.success) {
            status.textContent = '✅ Verified! Redirecting…';
            window.location.href = verifyData.redirect || '/dashboard';
        } else {
            throw new Error(verifyData.error || 'Verification failed.');
        }
    } catch (err) {
        status.textContent = '❌ ' + err.message;
        btn.disabled = false;
    }
}
</script>
<?php endif; ?>
