<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php if ($notices): ?>
    <div class="notice notice-success">
        <ul>
            <?php foreach ($notices as $notice): ?>
                <li><?= esc_html($notice) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="notice notice-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= esc_html($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
