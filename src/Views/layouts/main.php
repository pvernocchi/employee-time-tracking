<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Employee Time Tracker') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/dashboard">⏱️ Time Tracker</a>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard" class="<?= ($_SERVER['REQUEST_URI'] === '/dashboard' || $_SERVER['REQUEST_URI'] === '/') ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="/clock" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/clock') ? 'active' : '' ?>">Clock In/Out</a></li>
            <li><a href="/timesheet" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/timesheet') ? 'active' : '' ?>">Timesheet</a></li>
            <li><a href="/leave" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/leave') ? 'active' : '' ?>">Leave</a></li>
            <?php if (\App\Core\Auth::isManager()): ?>
            <li class="nav-dropdown">
                <a href="#" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin') ? 'active' : '' ?>">Admin ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="/admin/employees">Employees</a></li>
                    <li><a href="/admin/leave">Leave Requests</a></li>
                    <li><a href="/admin/reports">Reports</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
        <div class="nav-user">
            <span><?= htmlspecialchars(\App\Core\Auth::user()['first_name'] ?? '') ?></span>
            <a href="/logout" class="btn btn-sm btn-outline">Logout</a>
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
        <p>&copy; <?= date('Y') ?> Employee Time Tracker</p>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
