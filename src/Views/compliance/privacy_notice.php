<?php $title = $t('compliance.privacy.title'); ?>

<div class="page-header">
    <h1>🔒 <?= htmlspecialchars($t('compliance.privacy.heading')) ?></h1>
</div>

<div class="card">
    <h2><?= htmlspecialchars($t('compliance.privacy.data_protection_info')) ?></h2>
    <p><em><?= htmlspecialchars($t('compliance.privacy.intro')) ?></em></p>

    <h3><?= htmlspecialchars($t('compliance.privacy.section1_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section1_body')) ?></p>

    <h3><?= htmlspecialchars($t('compliance.privacy.section2_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section2_intro')) ?></p>
    <ul>
        <li><?= htmlspecialchars($t('compliance.privacy.section2_item1')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section2_item2')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section2_item3')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section2_item4')) ?></li>
    </ul>

    <h3><?= htmlspecialchars($t('compliance.privacy.section3_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section3_intro')) ?></p>
    <ul>
        <li><?= htmlspecialchars($t('compliance.privacy.section3_item1')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section3_item2')) ?></li>
    </ul>

    <h3><?= htmlspecialchars($t('compliance.privacy.section4_title')) ?></h3>
    <ul>
        <li><?= htmlspecialchars($t('compliance.privacy.section4_item1')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section4_item2')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section4_item3')) ?></li>
    </ul>

    <h3><?= htmlspecialchars($t('compliance.privacy.section5_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section5_body')) ?></p>

    <h3><?= htmlspecialchars($t('compliance.privacy.section6_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section6_intro')) ?></p>
    <ul>
        <li><?= htmlspecialchars($t('compliance.privacy.section6_item1')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section6_item2')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section6_item3')) ?></li>
    </ul>

    <h3><?= htmlspecialchars($t('compliance.privacy.section7_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section7_intro')) ?></p>
    <ul>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item1')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item2')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item3')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item4')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item5')) ?></li>
        <li><?= htmlspecialchars($t('compliance.privacy.section7_item6')) ?></li>
    </ul>
    <p><?= htmlspecialchars($t('compliance.privacy.section7_export_prefix')) ?> <a href="/timesheet"><?= htmlspecialchars($t('nav.timesheet')) ?></a>.</p>

    <h3><?= htmlspecialchars($t('compliance.privacy.section8_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section8_body')) ?> <a href="https://www.aepd.es" target="_blank">www.aepd.es</a></p>

    <h3><?= htmlspecialchars($t('compliance.privacy.section9_title')) ?></h3>
    <p><?= htmlspecialchars($t('compliance.privacy.section9_body')) ?></p>
</div>

<div class="card mt-2">
    <h2><?= htmlspecialchars($t('compliance.privacy.ack_title')) ?></h2>
    <p><?= htmlspecialchars($t('compliance.privacy.ack_body')) ?></p>
    <form method="POST" action="/compliance/consent">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="consent_type" value="time_tracking_privacy_notice">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('compliance.privacy.ack_button')) ?></button>
    </form>
</div>
