<?php $title = 'SMTP Settings'; ?>

<div class="page-header">
    <h1>⚙️ SMTP Settings</h1>
    <p class="text-muted">Configure the SMTP server used for sending notification emails.</p>
</div>

<div class="card">
    <form method="POST" action="/admin/settings/smtp">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <h2>Connection</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="smtp_host">SMTP Host</label>
                <input type="text" id="smtp_host" name="smtp_host"
                       value="<?= htmlspecialchars($settings['smtp_host']) ?>"
                       placeholder="smtp.example.com">
            </div>
            <div class="form-group">
                <label for="smtp_port">Port</label>
                <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535"
                       value="<?= htmlspecialchars($settings['smtp_port']) ?>"
                       placeholder="587">
            </div>
        </div>

        <div class="form-group">
            <label for="smtp_encryption">Encryption</label>
            <select id="smtp_encryption" name="smtp_encryption">
                <option value="none"  <?= $settings['smtp_encryption'] === 'none'  ? 'selected' : '' ?>>None</option>
                <option value="tls"   <?= $settings['smtp_encryption'] === 'tls'   ? 'selected' : '' ?>>STARTTLS (TLS on standard port)</option>
                <option value="ssl"   <?= $settings['smtp_encryption'] === 'ssl'   ? 'selected' : '' ?>>SSL/TLS (implicit TLS, e.g. port 465)</option>
            </select>
            <small class="text-muted">
                STARTTLS upgrades a plain connection to TLS (recommended for port 587).
                SSL/TLS uses a TLS connection from the start (typical for port 465).
            </small>
        </div>

        <h2>Authentication</h2>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="smtp_auth" id="smtp_auth" value="1"
                       <?= $settings['smtp_auth'] === '1' ? 'checked' : '' ?>>
                Enable SMTP Authentication
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="smtp_username">Username</label>
                <input type="text" id="smtp_username" name="smtp_username"
                       value="<?= htmlspecialchars($settings['smtp_username']) ?>"
                       placeholder="user@example.com"
                       autocomplete="username">
            </div>
            <div class="form-group">
                <label for="smtp_password">Password</label>
                <input type="password" id="smtp_password" name="smtp_password"
                       placeholder="Leave blank to keep current password"
                       autocomplete="new-password">
                <?php if ($settings['smtp_password'] !== ''): ?>
                    <small class="text-muted">A password is currently stored. Enter a new one to replace it.</small>
                <?php endif; ?>
            </div>
        </div>

        <h2>Sender</h2>

        <div class="form-row">
            <div class="form-group">
                <label for="smtp_from_email">From Email</label>
                <input type="email" id="smtp_from_email" name="smtp_from_email"
                       value="<?= htmlspecialchars($settings['smtp_from_email']) ?>"
                       placeholder="noreply@example.com">
            </div>
            <div class="form-group">
                <label for="smtp_from_name">From Name</label>
                <input type="text" id="smtp_from_name" name="smtp_from_name"
                       value="<?= htmlspecialchars($settings['smtp_from_name']) ?>"
                       placeholder="Employee Time Tracker">
            </div>
        </div>

        <h2>Options</h2>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="smtp_enabled" id="smtp_enabled" value="1"
                       <?= $settings['smtp_enabled'] === '1' ? 'checked' : '' ?>>
                Enable SMTP (use this server for sending emails)
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="smtp_log_enabled" id="smtp_log_enabled" value="1"
                       <?= $settings['smtp_log_enabled'] === '1' ? 'checked' : '' ?>>
                Enable SMTP logging (writes to <code>logs/smtp.log</code> — useful for troubleshooting)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top:1.5rem;">
    <h2>Send Test Email</h2>
    <p class="text-muted">Send a test message using the current (saved) SMTP configuration.</p>
    <form method="POST" action="/admin/settings/smtp/test">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="test_email">Recipient Email</label>
                <input type="email" id="test_email" name="test_email" placeholder="you@example.com" required>
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-outline">Send Test Email</button>
            </div>
        </div>
    </form>
</div>
