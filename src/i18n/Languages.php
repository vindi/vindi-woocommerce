<?php
namespace VindiPaymentGateways;

  class VindiLanguages {
    public function __construct()
    {
        add_action('init', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
      load_plugin_textdomain( 'vindi-payment-gateway', false, plugin_dir_path( __FILE__ ) . '/languages/' );
    }
  }
;
