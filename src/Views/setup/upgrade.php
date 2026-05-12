<div class="auth-card setup-card">
    <div class="auth-header">
        <h1>⬆️ Database Upgrade Required</h1>
        <p>A newer release needs to update the database before the application can continue.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($status['legacyInstall'])): ?>
        <div class="alert alert-success">A legacy installation was detected. The upgrader will register the existing schema and apply the new migration tracking tables.</div>
    <?php endif; ?>

    <div class="setup-status-grid">
        <div class="card">
            <h2>Versions</h2>
            <ul class="setup-list">
                <li><strong>Current version:</strong> <?= htmlspecialchars((string) ($status['currentVersion'] ?? 'legacy / unknown')) ?></li>
                <li><strong>Target version:</strong> <?= htmlspecialchars((string) ($status['targetVersion'] ?? 'n/a')) ?></li>
                <li><strong>Pending migrations:</strong> <?= count($status['pendingMigrations'] ?? []) ?></li>
            </ul>
        </div>
        <div class="card">
            <h2>Pending steps</h2>
            <ol class="setup-list">
                <?php foreach ($status['pendingMigrations'] as $migration): ?>
                    <li><strong><?= htmlspecialchars($migration['version']) ?></strong> — <?= htmlspecialchars($migration['name']) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>

    <form method="POST" action="/install/upgrade" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">Run Upgrade</button>
        </div>
    </form>
</div>
