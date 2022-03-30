<?php

namespace EffectConnect\Marketplaces\Model;

use WC_Payment_Gateway;

class ECPayment extends WC_Payment_Gateway
{
    public function __construct() {

        $this->id                 = 'effectconnect_payment';
        $this->title                 = 'EffectConnect Payment';
        $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
        $this->has_fields         = false;
        $this->method_title       = __('EffectConnect Marketplaces Payment', 'effectconnect_marketplaces');
        $this->method_description = __('Custom payment method for imported EffectConnect orders.', 'effectconnect_marketplaces');
        $this->enabled            = "yes";
    }

    function init() {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Actions
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    }
}