<?php

namespace EffectConnect\Marketplaces\Model;

use WC_Shipping_Method;

class ECShipping extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->id                 = 'effectconnect_shipping';
        $this->method_title       = __('EffectConnect Marketplaces Shipping', 'effectconnect_marketplaces');
        $this->method_description = __('Use the standard EffectConnect shipping method', 'effectconnect_marketplaces');

        $this->enabled            = "yes";

        $this->init();
    }

    function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();
    }
}