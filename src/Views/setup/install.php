<?php
$dbDefaults = $defaults['database'] ?? [];
$appDefaults = $defaults['app'] ?? [];
$value = static function (string $key, mixed $fallback = '') use ($old): mixed {
    return $old[$key] ?? $fallback;
};
?>
<div class="auth-card setup-card">
    <div class="auth-header">
        <h1>⚙️ Employee Time Tracker Setup</h1>
        <p>Create the configuration, install the database, and create the first administrator.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($status['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($status['message']) ?></div>
    <?php endif; ?>

    <?php if (!$status['configWritable']): ?>
        <div class="alert alert-error">The installer cannot write to <code>config/config.php</code>. Please adjust file permissions and retry.</div>
    <?php endif; ?>

    <div class="setup-status-grid">
        <div class="card">
            <h2>Environment</h2>
            <ul class="setup-list">
                <li><strong>Config file:</strong> <?= $status['configExists'] ? 'Found' : 'Will be created' ?></li>
                <li><strong>Config writable:</strong> <?= $status['configWritable'] ? 'Yes' : 'No' ?></li>
                <li><strong>Database connection:</strong> <?= $status['databaseConnected'] ? 'Working' : 'Not checked yet' ?></li>
                <li><strong>Existing users:</strong> <?= (int) ($status['usersCount'] ?? 0) ?></li>
            </ul>
        </div>
        <div class="card">
            <h2>How it works</h2>
            <ul class="setup-list">
                <li>Writes <code>config/config.php</code> from the form below</li>
                <li>Runs any pending database migrations automatically</li>
                <li>Creates the first administrator when the users table is empty</li>
                <li>Future releases will route pending upgrades to the upgrader screen</li>
            </ul>
        </div>
    </div>

    <form method="POST" action="/install" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="setup-section">
            <h2>Application</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="app_name">Application Name</label>
                    <input type="text" id="app_name" name="app_name" required value="<?= htmlspecialchars((string) $value('app_name', $appDefaults['name'] ?? 'Employee Time Tracker')) ?>">
                </div>
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <input type="text" id="timezone" name="timezone" required value="<?= htmlspecialchars((string) $value('timezone', $appDefaults['timezone'] ?? 'Europe/Madrid')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="app_url">Application URL</label>
                <input type="url" id="app_url" name="app_url" required placeholder="https://example.com" value="<?= htmlspecialchars((string) $value('app_url', $appDefaults['url'] ?? '')) ?>">
            </div>
        </div>

        <div class="setup-section">
            <h2>Database</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="db_host">Host</label>
                    <input type="text" id="db_host" name="db_host" required value="<?= htmlspecialchars((string) $value('db_host', $dbDefaults['host'] ?? 'localhost')) ?>">
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" required value="<?= htmlspecialchars((string) $value('db_name', $dbDefaults['name'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" id="db_user" name="db_user" required value="<?= htmlspecialchars((string) $value('db_user', $dbDefaults['user'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                <div class="form-group">
                    <label for="db_charset">Charset</label>
                    <input type="text" id="db_charset" name="db_charset" value="<?= htmlspecialchars((string) $value('db_charset', $dbDefaults['charset'] ?? 'utf8mb4')) ?>">
                </div>
            </div>
        </div>

        <div class="setup-section">
            <h2>First Administrator</h2>
            <p class="text-muted">Required only when no users exist yet.</p>
            <div class="form-row">
                <div class="form-group">
                    <label for="admin_first_name">First Name</label>
                    <input type="text" id="admin_first_name" name="admin_first_name" value="<?= htmlspecialchars((string) $value('admin_first_name')) ?>">
                </div>
                <div class="form-group">
                    <label for="admin_last_name">Last Name</label>
                    <input type="text" id="admin_last_name" name="admin_last_name" value="<?= htmlspecialchars((string) $value('admin_last_name')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="admin_email">Email</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars((string) $value('admin_email')) ?>">
                </div>
                <div class="form-group">
                    <label for="admin_password">Password</label>
                    <input type="password" id="admin_password" name="admin_password">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">Run Setup</button>
        </div>
    </form>
</div>
