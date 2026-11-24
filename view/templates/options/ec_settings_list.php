<?php

use EffectConnect\Marketplaces\Helper\TranslationHelper;

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1><?= esc_html(TranslationHelper::translate('EffectConnect - Global configuration')) ?></h1>

    <?php require dirname(__DIR__) . '/message.php'; ?>

    <form method="POST">
        <?php foreach ($options as $category => $optionValue): ?>
            <h2 class="title"><?= esc_html($category) ?></h2>
                <table class="form-table">
                    <?php foreach ($optionValue as $key => $setting): ?>
                        <tr>
                            <th>
                                <label for="<?= esc_attr($key) ?>"><?= esc_html($setting->getName()) ?></label>
                            </th>
                            <td>
                                <?php if ($setting->typeIsSelect()): ?>
                                    <select id="<?= esc_attr($key) ?>" name="<?= esc_attr($key) ?>">
                                        <?php foreach ($setting->getOptions() as $optionId => $optionName): ?>
                                            <option
                                                    <?php if ($optionId == $setting->getValue()): ?>selected="selected"<?php endif; ?>
                                                    value="<?= esc_attr($optionId) ?>"
                                            >
                                                <?= esc_html($optionName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($setting->typeIsText()): ?>
                                    <input type="text" id="<?= esc_attr($key) ?>" name="<?= esc_attr($key) ?>" value="<?= esc_attr($setting->getValue()) ?>" class="regular-text"/>
                                <?php elseif ($setting->typeIsNumber()): ?>
                                    <input type="number" id="<?= esc_attr($key) ?>" name="<?= esc_attr($key) ?>" value="<?= esc_attr($setting->getValue()) ?>" class="small-text"/>
                                <?php elseif ($setting->typeIsCheckbox()): ?>
                                    <input type="checkbox" id="<?= esc_attr($key) ?>" name="<?= esc_attr($key) ?>" value="1"
                                           <?php if ($setting->getValue() == '1'): ?>checked<?php endif; ?>
                                    />
                                <?php endif; ?>
                                <?php if ($setting->getDescription()): ?>
                                    <p class="description"><?= esc_html($setting->getDescription()) ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
        <?php endforeach; ?>
        <?= $button ?>
    </form>
</div>