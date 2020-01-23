<?php
/**
 * Plugin Name: Vindi WooCommerce
 * Plugin URI: #!
 * Description: Adiciona o gateway de pagamentos da Vindi para o WooCommerce.
 * Author: vindi
 * Author URI: https://www.vindi.com.br
 * Version: 1.0.0
 * Requires at least: 4.4
 * Text Domain: vindi-woocommerce
 * Domain Path: ./src/languages/
 *
 * Copyright: © 2014-2018 Vindi Tecnologia e Marketing LTDA
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
	global $path;

if ( ! defined( 'ABSPATH' ) || class_exists( 'WC_Vindi_Payment' )  ) {
	exit;
}

$path = plugin_dir_path( __FILE__ ) . 'src/';

/**
 * WooCommerce Vindi Require Main Class.
*/

require_once 'src/vindi-woocommerce.php';

$GLOBALS['vindi'] = WC_Vindi_Payment::instance();
