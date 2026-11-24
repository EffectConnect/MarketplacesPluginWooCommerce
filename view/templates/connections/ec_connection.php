<?php

use EffectConnect\Marketplaces\Helper\TranslationHelper;

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <?php if ($formData->getConnectionId() > 0): ?>
        <h1><?= esc_html(TranslationHelper::translate('EffectConnect - Edit connection')) ?></h1>
    <?php else: ?>
        <h1><?= esc_html(TranslationHelper::translate('EffectConnect - Add connection')) ?></h1>
    <?php endif; ?>

    <?php require dirname(__DIR__) . '/message.php'; ?>

    <form method="POST">
        <input type="hidden" name="connection_id" value="<?= esc_attr($formData->getConnectionId()) ?>"/>

        <h2 class="title"><?= esc_html(TranslationHelper::translate('General settings')) ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label for="connectionName"><?= esc_html(TranslationHelper::translate('Connection name')) ?></label>
                </th>
                <td>
                    <input type="text" id="connectionName" name="connection_name" value="<?= esc_attr($formData->getConnectionName()) ?>"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Used for internal reference only.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="publicKey"><?= esc_html(TranslationHelper::translate('Public key')) ?></label>
                </th>
                <td>
                    <input type="text" id="publicKey" name="public_key" value="<?= esc_attr($formData->getPublicKey()) ?>"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Find your public key in the API Key Management section in EffectConnect Marketplaces.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="privateKey"><?= esc_html(TranslationHelper::translate('Secret key')) ?></label>
                </th>
                <td>
                    <input type="password" id="privateKey" name="private_key" value="<?= esc_attr($formData->getPrivateKey()) ?>"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Find your secret key in the API Key Management section in EffectConnect Marketplaces.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="isActive"><?= esc_html(TranslationHelper::translate('Active')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getIsActive()): ?>checked<?php endif; ?>
                            type="checkbox" id="isActive" name="is_active" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('No export and import processes will run for inactive connections.')) ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?= esc_html(TranslationHelper::translate('Catalog export settings')) ?></h2>
        <table class="form-table">
            <?php if (count($activeWpmlLanguages) > 0 && $defaultWpmlLanguage): ?>
                <tr>
                    <th>
                        <label for="catalogExportWpmlLanguages"><?= esc_html(sprintf(TranslationHelper::translate('Export %s languages'), $wpmlPluginName)) ?></label>
                    </th>
                    <td>
                        <input
                                <?php if ($formData->getCatalogExportWpmlLanguages()): ?>checked<?php endif; ?>
                                type="checkbox" id="catalogExportWpmlLanguages" name="catalog_export_wpml_languages" value="1"/>
                        <p class="description">
                            <?= esc_html(sprintf(TranslationHelper::translate('We have detected the %s plugin. Choose this option if you want to export all active %s languages to EffectConnect. The default %s language will be used to choose which products to export and is used as fallback languages in case of missing translations.'), $wpmlPluginName, $wpmlPluginName, $wpmlPluginName)) ?><br/>
                            <?= esc_html(sprintf(TranslationHelper::translate('Active %s language codes: [%s].'), $wpmlPluginName, implode(', ', $activeWpmlLanguages))) ?><br/>
                            <?= esc_html(sprintf(TranslationHelper::translate('Default %s language code: [%s].'), $wpmlPluginName, $defaultWpmlLanguage)) ?>
                        </p>
                    </td>
                </tr>
            <?php else: ?>
                <input type="hidden" id="catalogExportWpmlLanguages" value="0"/>
            <?php endif; ?>
            <tr class="dependsOnInvertedCatalogExportWpmlLanguages">
                <th>
                    <label for="catalogExportLanguage"><?= esc_html(TranslationHelper::translate('Catalog language')) ?></label>
                </th>
                <td>
                    <select id="catalogExportLanguage" name="catalog_export_language">
                        <?php foreach ($formData->getLanguageOptions() as $languageId => $languageName): ?>
                            <option
                                    <?php if ($languageId == $formData->getCatalogExportLanguage()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($languageId) ?>">
                                <?= esc_html($languageName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select the general language that product details are written in for the catalog (this does not change anything in the catalog and is only used as an indicator for EffectConnect).')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportOnlyActive"><?= esc_html(TranslationHelper::translate('Only export active products')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportOnlyActive()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportOnlyActive" name="catalog_export_only_active" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('If this setting is enabled only products that are enabled will be exported to EffectConnect.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportTaxonomies"><?= esc_html(TranslationHelper::translate('Export taxonomies as attributes')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportTaxonomies()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportTaxonomies" name="catalog_export_taxonomies" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('If this setting is enabled all product taxonomies will be exported to EffectConnect as attributes.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportSkipRegenerateIds"><?= esc_html(TranslationHelper::translate('Skip regenerating IDs for simple products')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportSkipRegenerateIds()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportSkipRegenerateIds" name="catalog_export_skip_regenerate_ids" value="1"/>
                    <p class="description">
                        <?= esc_html(TranslationHelper::translate('If this setting is enabled SIMPLE products will not get a new ID if any of the attributes have been changed.')) ?><br/>
                        <?= esc_html(TranslationHelper::translate('Independant of this setting, IDs for VARIABLE products are always deduced from the products\'s attributes, since WooCommerce doesn\'t keep IDs for product variations.')) ?><br/>
                        <?= esc_html(TranslationHelper::translate('NOTE: be careful when enabling/disabling this setting! This could cause regeneration of offers on the marketplaces.')) ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportSpecialPrice"><?= esc_html(TranslationHelper::translate('Use special price')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportSpecialPrice()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportSpecialPrice" name="catalog_export_special_price" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('If enabled the original price will be exported to EffectConnect Marketplaces separately in case there is a sale price. If disabled only the original price is exported.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportEanAttribute"><?= esc_html(TranslationHelper::translate('EAN attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportEanAttribute" name="catalog_export_ean_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportEanAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="dependsOnCatalogExportEanAttribute">
                <th>
                    <label for="catalogExportSkipInvalidEan"><?= esc_html(TranslationHelper::translate('Exclude products with invalid EAN')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportSkipInvalidEan()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportSkipInvalidEan" name="catalog_export_skip_invalid_ean" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('When checked, products with invalid EAN will be excluded from export. Otherwise products will be exported without EAN, since EffectConnect won\'t except products with invalid EAN.')) ?></p>
                </td>
            </tr>
            <tr class="dependsOnCatalogExportEanAttribute">
                <th>
                    <label for="catalogExportEanLeadingZero"><?= esc_html(TranslationHelper::translate('Add leading zero to EAN')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getCatalogExportEanLeadingZero()): ?>checked<?php endif; ?>
                            type="checkbox" id="catalogExportEanLeadingZero" name="catalog_export_ean_leading_zero" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select whether the plugin should automatically add a leading zero to an EAN that consists of 12 characters. Products with invalid EAN will be exported without EAN field.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportTitleAttribute"><?= esc_html(TranslationHelper::translate('Title attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportTitleAttribute" name="catalog_export_title_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportTitleAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportDescriptionAttribute"><?= esc_html(TranslationHelper::translate('Description attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportDescriptionAttribute" name="catalog_export_description_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportDescriptionAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportCostAttribute"><?= esc_html(TranslationHelper::translate('Cost attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportCostAttribute" name="catalog_export_cost_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportCostAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportDeliveryAttribute"><?= esc_html(TranslationHelper::translate('Delivery attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportDeliveryAttribute" name="catalog_export_delivery_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportDeliveryAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="catalogExportBrandAttribute"><?= esc_html(TranslationHelper::translate('Brand attribute')) ?></label>
                </th>
                <td>
                    <select id="catalogExportBrandAttribute" name="catalog_export_brand_attribute">
                        <?php foreach ($formData->getAttributeOptions() as $groupName => $attributeOptions): ?>
                            <optgroup label="<?= esc_attr($groupName) ?>">
                                <?php foreach ($attributeOptions as $attributeId => $attributeName): ?>
                                    <option
                                            <?php if ($attributeId == $formData->getCatalogExportBrandAttribute()): ?>selected="selected"<?php endif; ?>
                                            value="<?= esc_attr($attributeId) ?>">
                                        <?= esc_html($attributeName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <h2 class="title"><?= esc_html(TranslationHelper::translate('Offer export settings')) ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label for="offerExportVirtualStockAmount"><?= esc_html(TranslationHelper::translate('Fictional stock amount')) ?></label>
                </th>
                <td>
                    <input type="number" id="offerExportVirtualStockAmount" name="offer_export_virtual_stock_amount" value="<?= esc_attr($formData->getOfferExportVirtualStockAmount()) ?>"/>
                    <p class="description">
                        <?= esc_html(TranslationHelper::translate('The stock amount to export in case product is in stock, but stock amount is unknown (backorders are enabled or stock management is disabled).')) ?>
                        <?= esc_html(TranslationHelper::translate('Possible values: 0-9999.')) ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="offerExportVirtualStockConditionalBackorders"><?= esc_html(TranslationHelper::translate('Use actual stock for backorder products with quantity above 0')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getOfferExportVirtualStockConditionalBackorders()): ?>checked<?php endif; ?>
                            type="checkbox" id="offerExportVirtualStockConditionalBackorders" name="offer_export_virtual_stock_conditional_backorders" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Normally the fictional stock is used for all products that allow backorders. Enable this checkbox if the fictional stock for backorder products should only be used for products that have quantity 0 (or less).')) ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?= esc_html(TranslationHelper::translate('Order import settings')) ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label for="orderImportSendEmails"><?= esc_html(TranslationHelper::translate('Send emails')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getOrderImportSendEmails()): ?>checked<?php endif; ?>
                            type="checkbox" id="orderImportSendEmails" name="order_import_send_emails" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Whether or not to let WooCommerce send order update emails to the customer and admin for each imported order. When disabled, no emails are sent when importing orders. When enabled, the WooCommerce email settings dictate which emails are sent.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportSkipTaxes"><?= esc_html(TranslationHelper::translate('Skip taxes')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getOrderImportSkipTaxes()): ?>checked<?php endif; ?>
                            type="checkbox" id="orderImportSkipTaxes" name="order_import_skip_taxes" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('If this option is enabled, no tax will be calculated for imported orders, otherwise the default WooCommerce tax settings will be used.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportOrderStatus"><?= esc_html(TranslationHelper::translate('Order status')) ?></label>
                </th>
                <td>
                    <select id="orderImportOrderStatus" name="order_import_order_status">
                        <?php foreach ($formData->getOrderStatusOptions() as $orderStatusId => $orderStatusName): ?>
                            <option
                                    <?php if ($orderStatusId === $formData->getOrderImportOrderStatus()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($orderStatusId) ?>">
                                <?= esc_html($orderStatusName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select what order status should be used for imported orders.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportIdPaymentModule"><?= esc_html(TranslationHelper::translate('Payment method')) ?></label>
                </th>
                <td>
                    <select id="orderImportIdPaymentModule" name="order_import_id_payment_module">
                        <?php foreach ($formData->getPaymentOptions() as $paymentId => $paymentName): ?>
                            <option
                                    <?php if ($paymentId === $formData->getOrderImportIdPaymentModule()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($paymentId) ?>">
                                <?= esc_html($paymentName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select the payment method for imported orders.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportIdCarrier"><?= esc_html(TranslationHelper::translate('Shipping method')) ?></label>
                </th>
                <td>
                    <select id="orderImportIdCarrier" name="order_import_id_carrier">
                        <?php foreach ($formData->getCarrierOptions() as $carrierId => $carrierName): ?>
                            <option
                                    <?php if ($carrierId === $formData->getOrderImportIdCarrier()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($carrierId) ?>">
                                <?= esc_html($carrierName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select the shipping method for imported orders.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportExternalFulfilment"><?= esc_html(TranslationHelper::translate('External fulfilment')) ?></label>
                </th>
                <td>
                    <select id="orderImportExternalFulfilment" name="order_import_external_fulfilment">
                        <?php foreach ($formData->getExternalFulfilmentOptions() as $externalFulfilmentId => $externalFulfilmentName): ?>
                            <option
                                    <?php if ($externalFulfilmentId === $formData->getOrderImportExternalFulfilment()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($externalFulfilmentId) ?>">
                                <?= esc_html($externalFulfilmentName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Whether to import orders that are externally fulfilled or not. Internally fulfilled orders have status paid in EffectConnect. Externally fulfilled orders have status completed and tag external_fulfilled.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportExternalFulfilmentOrderStatus"><?= esc_html(TranslationHelper::translate('Order status externally fulfilled orders')) ?></label>
                </th>
                <td>
                    <select id="orderImportExternalFulfilmentOrderStatus" name="order_import_external_fulfilment_order_status">
                        <?php foreach ($formData->getExternalFulfilmentOrderStatusOptions() as $orderStatusId => $orderStatusName): ?>
                            <option
                                    <?php if ($orderStatusId === $formData->getOrderImportExternalFulfilmentOrderStatus()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($orderStatusId) ?>">
                                <?= esc_html($orderStatusName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Optionally select what order status should be used for imported orders that are externally fulfilled.')) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="orderImportSkipReduceStock"><?= esc_html(TranslationHelper::translate('Skip stock reduction')) ?></label>
                </th>
                <td>
                    <input
                            <?php if ($formData->getOrderImportSkipReduceStock()): ?>checked<?php endif; ?>
                            type="checkbox" id="orderImportSkipReduceStock" name="order_import_skip_reduce_stock" value="1"/>
                    <p class="description"><?= esc_html(TranslationHelper::translate('If this option is enabled, WooCommerce will skip stock reduction for imported orders that are externally fulfilled.')) ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?= esc_html(TranslationHelper::translate('Shipment export')) ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label for="shipmentExportWhen"><?= esc_html(TranslationHelper::translate('When to update order')) ?></label>
                </th>
                <td>
                    <select id="shipmentExportWhen" name="shipment_export_when">
                        <?php foreach ($formData->getShipmentExportWhenOptions() as $key => $name): ?>
                            <option
                                    <?php if ($key == $formData->getShipmentExportWhen()): ?>selected="selected"<?php endif; ?>
                                    value="<?= esc_attr($key) ?>">
                                <?= esc_html($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Select when to update the order status in EffectConnect to shipped.')) ?></p>
                </td>
            </tr>
            <tr class="dependsOnShipmentExportWhen">
                <th>
                    <label for="shipmentExportTrackingCodes"><?= esc_html(TranslationHelper::translate('Tracking code format')) ?></label>
                </th>
                <td>
                    <textarea id="shipmentExportTrackingCodes" name="shipment_export_tracking_codes" rows="5" cols="60"><?= esc_textarea($formData->getShipmentExportTrackingCodes()) ?></textarea>
                    <p class="description"><?= esc_html(TranslationHelper::translate('Tracking codes format to look for in order comments. Use [code] as placeholder for the tracking code. For example: "Tracking code: [code]". Separate multitple format by new lines.')) ?></p>
                </td>
            </tr>
            <tr class="dependsOnShipmentExportWhenWait">
                <th>
                    <label for="shipmentExportDelay"><?= esc_html(TranslationHelper::translate('Waiting time in minutes')) ?></label>
                </th>
                <td>
                    <input type="number" id="shipmentExportDelay" name="shipment_export_delay" value="<?= esc_attr($formData->getShipmentExportDelay()) ?>"/>
                    <p class="description">
                        <?= esc_html(TranslationHelper::translate('Minutes to wait after an order status changes in WooCommerce before sending the update to EffectConnect — giving the order time to receive a tracking code first.')) ?>
                        <?= esc_html(TranslationHelper::translate('A value of 0 means there is no waiting time and the update is sent immediately (default behaviour).')) ?>
                    </p>
                </td>
            </tr>
        </table>

        <?= $button ?>
    </form>
</div>

<style>
    .dependsOnShipmentExportWhen,
    .dependsOnShipmentExportWhenWait,
    .dependsOnInvertedCatalogExportWpmlLanguages,
    .dependsOnCatalogExportEanAttribute {
        display: none;
    }
</style>

<script>
    jQuery(document).ready(function() {
        let catalogExportEanAttributeElement = jQuery('#catalogExportEanAttribute');
        function setStates() {
            if (catalogExportEanAttributeElement.val()) {
                jQuery('.dependsOnCatalogExportEanAttribute').show();
            } else {
                jQuery('.dependsOnCatalogExportEanAttribute').hide();
            }
        }
        catalogExportEanAttributeElement.on('change', function() {
            setStates();
        });
        setStates();

        let catalogExportWpmlLanguagesElement = jQuery('#catalogExportWpmlLanguages');
        function setLanguages() {
            if (catalogExportWpmlLanguagesElement.is(':checked')) {
                jQuery('.dependsOnInvertedCatalogExportWpmlLanguages').hide();
            } else {
                jQuery('.dependsOnInvertedCatalogExportWpmlLanguages').show();
            }
        }
        catalogExportWpmlLanguagesElement.on('change', function() {
            setLanguages();
        });
        setLanguages();

        let shipmentExportWhenElement = jQuery('#shipmentExportWhen');
        function setTrackingCodes() {
            if (shipmentExportWhenElement.val().startsWith('wc-')) {
                jQuery('.dependsOnShipmentExportWhen').show();
                jQuery('.dependsOnShipmentExportWhenWait').show();
            } else {
                jQuery('.dependsOnShipmentExportWhen').hide();
                jQuery('.dependsOnShipmentExportWhenWait').hide();
            }
        }
        shipmentExportWhenElement.on('change', function() {
            setTrackingCodes();
        });
        setTrackingCodes();
    });
</script>