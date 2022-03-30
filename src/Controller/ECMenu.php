<?php

namespace EffectConnect\Marketplaces\Controller;

use EffectConnect\Marketplaces\Helper\TranslationHelper;

class ECMenu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'ec_add_plugin_page'));
    }

    /**
     * Generates EffectConnect menu with submenus in wordpress.
     */
    public function ec_add_plugin_page()
    {
        add_menu_page(
            'EffectConnect-marketplaces', // page_title
            TranslationHelper::translate('EffectConnect'), // menu_title
            'manage_options', // capability
            'effectconnect', // menu_slug
            array(new ConnectionController(), 'init'), //callback function
            'data:image/svg+xml;base64,' . base64_encode('<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128.8 62.25"><path d="M119.69,40.62a31.14,31.14,0,0,0-44,0L64.4,51.9,53.13,40.62a31.12,31.12,0,1,0,0,44L64.4,73.37,75.67,84.64a31.13,31.13,0,0,0,44-44ZM42.4,73.91a16,16,0,1,1,0-22.56L53.68,62.62h0Zm66.56,0a16,16,0,0,1-22.56,0L75.13,62.64h0L86.4,51.35A16,16,0,1,1,109,73.91Z" transform="translate(0 -31.51)" style="fill:#a0a5aa"/></svg>'), // icon_url
            59 // Below WooCommerce
        );
        add_submenu_page(
            'effectconnect',
            TranslationHelper::translate('Connections'), //page title
            TranslationHelper::translate('Connections'), //menu title
            'manage_options', //capability,
            'connectionoptions',//menu slug
            array(new ConnectionController(), 'init') //callback function
        );
        add_submenu_page(
            'effectconnect',
            'EC_Settings', //page title
            TranslationHelper::translate('Settings'), //menu title
            'manage_options', //capability,
            'plugin_settings',//menu slug
            array(new ConfigController(), 'init') //callback function
        );
        add_submenu_page(
            'effectconnect',
            'EC_Logs', //page title
            TranslationHelper::translate('Logs'), //menu title
            'manage_options', //capability,
            'ec_logs',//menu slug
            array(new LogController(), 'init') //callback function
        );

        // WC will automatically duplicate the parent menu item into a sub menu item, this is unwanted, so let's remove it.
        remove_submenu_page('effectconnect', 'effectconnect');
    }
}
