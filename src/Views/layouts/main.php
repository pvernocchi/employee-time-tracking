<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale ?? 'es') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? $t('app.name')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/dashboard">⏱️ <?= htmlspecialchars($t('app.short_name')) ?></a>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard" class="<?= ($_SERVER['REQUEST_URI'] === '/dashboard' || $_SERVER['REQUEST_URI'] === '/') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.dashboard')) ?></a></li>
            <li><a href="/clock" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/clock') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.clock')) ?></a></li>
            <li><a href="/timesheet" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/timesheet') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.timesheet')) ?></a></li>
            <li><a href="/leave" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/leave') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.leave')) ?></a></li>
            <?php if (\App\Core\Auth::isManager()): ?>
            <li class="nav-dropdown">
                <a href="#" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.admin')) ?> ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="/admin/employees"><?= htmlspecialchars($t('nav.employees')) ?></a></li>
                    <li><a href="/admin/leave"><?= htmlspecialchars($t('nav.leave_requests')) ?></a></li>
                    <li><a href="/admin/reports"><?= htmlspecialchars($t('nav.reports')) ?></a></li>
                    <?php if (\App\Core\Auth::isAdmin()): ?>
                    <li><a href="/compliance"><?= htmlspecialchars($t('nav.compliance')) ?></a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::isInspector()): ?>
            <li><a href="/inspector" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/inspector') ? 'active' : '' ?>"><?= htmlspecialchars($t('nav.inspector')) ?></a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-user">
            <span><?= htmlspecialchars($t('layout.language')) ?>:</span>
            <?php foreach ($supportedLocales ?? [] as $locale): ?>
                <a href="<?= htmlspecialchars(\App\Core\I18n::urlWithLang($locale)) ?>" class="btn btn-sm <?= ($locale === ($currentLocale ?? '')) ? 'btn-primary' : 'btn-outline' ?>"><?= strtoupper(htmlspecialchars($locale)) ?></a>
            <?php endforeach; ?>
            <span><?= htmlspecialchars(\App\Core\Auth::user()['first_name'] ?? '') ?></span>
            <a href="/logout" class="btn btn-sm btn-outline"><?= htmlspecialchars($t('nav.logout')) ?></a>
        </div>
    </nav>

    <main class="container">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($t('app.name')) ?></p>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
