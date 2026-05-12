<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale ?? 'es') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? $t('auth.login_page_title', ['app' => $t('app.name')])) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= htmlspecialchars($bodyClass ?? 'auth-body') ?>">
    <div class="<?= htmlspecialchars($containerClass ?? 'auth-container') ?>">
        <div style="display:flex; justify-content:flex-end; gap:0.35rem; margin-bottom:0.75rem;">
            <span style="font-size:0.9rem; align-self:center;"><?= htmlspecialchars($t('layout.language')) ?>:</span>
            <?php foreach ($supportedLocales ?? [] as $locale): ?>
                <a href="<?= htmlspecialchars(\App\Core\I18n::urlWithLang($locale)) ?>" class="btn btn-sm <?= ($locale === ($currentLocale ?? '')) ? 'btn-primary' : 'btn-outline' ?>"><?= strtoupper(htmlspecialchars($locale)) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?= $content ?>
    </div>
</body>
</html>
