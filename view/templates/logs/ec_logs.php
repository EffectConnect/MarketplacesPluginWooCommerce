<?php

use EffectConnect\Marketplaces\Helper\TranslationHelper;

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1>EffectConnect - Logs</h1>

    <?php require dirname(__DIR__) . '/message.php'; ?>

    <div>
        <p><?= esc_html(TranslationHelper::translate('The Effectconnect Markplaces plugin generates log and export files in the following folder:')) ?></p>
        <pre><?= esc_html($dataFolder) ?></pre>
        <p><?= esc_html(TranslationHelper::translate('These files can be used by the Effectconnect support team to inspect errors. Use the download button below.')) ?></p>
        <p><?= esc_html(sprintf(TranslationHelper::translate('The files are cleaned after %s day(s).'), $logExpirationDays)) ?></p>
    </div>
    <div>
        <form method="POST">
            <?= $downloadButton ?>
        </form>
    </div>
</div>