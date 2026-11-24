<?php

use EffectConnect\Marketplaces\Helper\TranslationHelper;

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?= esc_html(TranslationHelper::translate('EffectConnect - Connections')) ?></h1>
    <?php if (!empty($connections)): ?>
        <a href="<?= esc_url($url . '&add') ?>" class="page-title-action"><?= esc_html(TranslationHelper::translate('Add new')) ?></a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php require dirname(__DIR__) . '/message.php'; ?>

    <?php if (!empty($connections)): ?>
        <p><?= esc_html(count($connections)) ?> item(s)</p>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
            <tr>
                <th><?= esc_html(TranslationHelper::translate('ID')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Active')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Name')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Public key')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($connections as $conn): ?>
                <tr>
                    <td>
                        <?= esc_html($conn->connection_id) ?>
                    </td>
                    <td>
                        <?= esc_html($conn->is_active ? TranslationHelper::translate('Yes') : TranslationHelper::translate('No')) ?>
                    </td>
                    <td>
                        <a class="row-title" href="<?= esc_url($url . '&edit&connection_id=' . $conn->connection_id) ?>"><?= esc_html($conn->connection_name) ?></a>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?= esc_url($url . '&edit&connection_id=' . $conn->connection_id) ?>"><?= esc_html(TranslationHelper::translate('Edit')) ?></a> |
                            </span>
                            <span class="trash">
                                <a href="<?= esc_url($url . '&delete&connection_id=' . $conn->connection_id) ?>" onclick="return confirm(<?= esc_attr(wp_json_encode(TranslationHelper::translate('Are you sure you want to delete this connection?'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"><?= esc_html(TranslationHelper::translate('Delete')) ?></a>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?= esc_html($conn->public_key) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th><?= esc_html(TranslationHelper::translate('ID')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Active')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Name')) ?></th>
                <th><?= esc_html(TranslationHelper::translate('Public key')) ?></th>
            </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <div class="notice notice-success">
            <p>
                <?= esc_html(TranslationHelper::translate('This plugin allows your WooCommerce webshop to connect with EffectConnect Marketplaces.')) ?>
                <?= esc_html(TranslationHelper::translate('You will need an EffectConnect Marketplaces API key and create a connection below to start using this plugin.')) ?>
                <a target="_blank" href="https://www.effectconnect.com"><?= esc_html(TranslationHelper::translate('Visit our support webite for more information on how to do this.')) ?></a>
            </p>
            <p><a class="button-primary" href="<?= esc_url($url . '&add') ?>" class="page-title-action"><?= esc_html(TranslationHelper::translate('Add new connection')) ?></a></p>
        </div>
    <?php endif; ?>
</div>