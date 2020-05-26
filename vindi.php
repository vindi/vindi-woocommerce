<?php

/**
 * Plugin Name: Vindi WooCommerce
 * Plugin URI: #!
 * Description: Adiciona o gateway de pagamento da Vindi para o WooCommerce.
 * Author: vindi
 * Author URI: https://www.vindi.com.br
 * Version: 1.0.0
 * Requires at least: 4.4
 * Text Domain: vindi-woocommerce
 *
 * Domain Path: ./src/languages/
 *
 * @package Vindi
 *
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

// Adding the development variables
if(file_exists(plugin_dir_path(__FILE__) . '.env.php')) {
  include plugin_dir_path(__FILE__) . '.env.php';
}

// Adding the variables
require plugin_dir_path(__FILE__) . '/src/utils/DefinitionVariables.php';

// Adding dependency validator
require_once plugin_dir_path(__FILE__) . '/src/validators/Dependencies.php';

if (VindiDependencies::check()) {

  require_once VINDI_PATH . 'src/VindiWoocommerce.php';

  if (!defined('VINDI_TESTS')) {
    // In tests we run the instance manually.
    // $GLOBALS['vindi'] = WC_Vindi_Payment::get_instance();
  }
}
