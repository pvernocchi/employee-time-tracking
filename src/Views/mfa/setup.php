<?php $title = 'Two-Factor Authentication Setup'; ?>

<div class="page-header">
    <h1>🔑 Two-Factor Authentication</h1>
    <?php if (!empty($forceSetup)): ?>
        <div class="alert alert-error" style="margin-top:.5rem;">
            ⚠️ Your account requires MFA. Please enroll an authenticator method to continue.
        </div>
    <?php endif; ?>
</div>

<!-- Enrolled Methods -->
<div class="card">
    <h2>Enrolled Methods (<?= count($methods) ?>)</h2>
    <?php if (empty($methods)): ?>
        <p class="text-muted">No MFA methods enrolled yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Enrolled</th>
                    <th>Last Used</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $m): ?>
                <tr>
                    <td><?= $m['type'] === 'totp' ? '📱 TOTP' : '🔑 Security Key' ?></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= $m['last_used_at'] ? date('d/m/Y H:i', strtotime($m['last_used_at'])) : '—' ?></td>
                    <td>
                        <form method="POST" action="/mfa/remove/<?= $m['id'] ?>" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline"
                                onclick="return confirm('Remove this MFA method?')">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Add new methods -->
<div class="card mt-2">
    <h2>Add an MFA Method</h2>
    <div class="form-row">
        <div class="card" style="flex:1; text-align:center; padding:1.5rem;">
            <div style="font-size:3rem;">📱</div>
            <h3>Authenticator App</h3>
            <p class="text-muted">Works with Microsoft Authenticator, Google Authenticator, Authy, and any TOTP app.</p>
            <a href="/mfa/enroll/totp" class="btn btn-primary">Set Up</a>
        </div>
        <div class="card" style="flex:1; text-align:center; padding:1.5rem;">
            <div style="font-size:3rem;">🔑</div>
            <h3>Security Key / Passkey</h3>
            <p class="text-muted">Works with YubiKey, Windows Hello, Touch ID, and other FIDO2/WebAuthn devices.</p>
            <button type="button" class="btn btn-primary" id="webauthn-register-btn" onclick="startWebAuthnRegister()">Set Up</button>
            <p id="webauthn-register-name-area" style="margin-top:.75rem; display:none;">
                <input type="text" id="webauthn-device-name" placeholder="Device name (e.g. YubiKey 5)" style="width:100%; margin-bottom:.5rem;">
                <button type="button" class="btn btn-primary btn-block" onclick="doWebAuthnRegister()">Register Key</button>
            </p>
            <p id="webauthn-register-status" class="text-muted" style="margin-top:.5rem;"></p>
        </div>
    </div>
</div>

<?php if (!\App\Core\Auth::check()): ?>
<!-- During mandatory setup from pending login -->
<div style="margin-top:1rem; text-align:center;">
    <a href="/logout" class="text-muted" style="font-size:.875rem;">Cancel and sign out</a>
</div>
<?php endif; ?>

<script>
function startWebAuthnRegister() {
    document.getElementById('webauthn-register-name-area').style.display = '';
    document.getElementById('webauthn-register-btn').style.display = 'none';
    document.getElementById('webauthn-device-name').focus();
}

async function doWebAuthnRegister() {
    const status     = document.getElementById('webauthn-register-status');
    const deviceName = document.getElementById('webauthn-device-name').value.trim() || 'Security Key';

    status.textContent = 'Requesting challenge…';

    try {
        const challengeRes = await fetch('/mfa/webauthn/register-challenge', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({})
        });
        const challengeData = await challengeRes.json();
        if (challengeData.error) throw new Error(challengeData.error);

        const b64ToAb = s => Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
        const abToB64 = ab => btoa(String.fromCharCode(...new Uint8Array(ab)));

        const pk = challengeData.publicKey;
        pk.challenge = b64ToAb(pk.challenge);
        pk.user.id  = b64ToAb(pk.user.id);
        if (pk.excludeCredentials) {
            pk.excludeCredentials = pk.excludeCredentials.map(c => ({...c, id: b64ToAb(c.id)}));
        }

        status.textContent = 'Touch your security key or use your passkey…';
        const credential = await navigator.credentials.create({publicKey: pk});

        status.textContent = 'Verifying…';
        const verifyRes = await fetch('/mfa/webauthn/register-verify', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                clientDataJSON:    abToB64(credential.response.clientDataJSON),
                attestationObject: abToB64(credential.response.attestationObject),
                device_name:       deviceName,
            })
        });
        const verifyData = await verifyRes.json();

        if (verifyData.success) {
            status.textContent = '✅ Registered! Redirecting…';
            window.location.href = verifyData.redirect || '/mfa/setup';
        } else {
            throw new Error(verifyData.error || 'Registration failed.');
        }
    } catch (err) {
        status.textContent = '❌ ' + err.message;
        document.getElementById('webauthn-register-btn').style.display = '';
        document.getElementById('webauthn-register-name-area').style.display = 'none';
    }
}
</script>
