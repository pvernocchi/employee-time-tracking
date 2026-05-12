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
        <div class="language-switch">
            <span class="language-switch-label"><?= htmlspecialchars($t('layout.language')) ?>:</span>
            <?php foreach ($supportedLocales ?? [] as $locale): ?>
                <?php $itemLocaleMeta = $localeMeta($locale); ?>
                <a href="<?= htmlspecialchars(\App\Core\I18n::urlWithLang($locale)) ?>" class="btn btn-sm language-switch-item <?= ($locale === ($currentLocale ?? '')) ? 'btn-primary' : 'btn-outline' ?>">
                    <img src="<?= htmlspecialchars($itemLocaleMeta['flag']) ?>" alt="" class="flag-icon" aria-hidden="true">
                    <?= htmlspecialchars($itemLocaleMeta['abbr']) ?>
                </a>
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
