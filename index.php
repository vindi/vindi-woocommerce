<?php
/**
 * Plugin Name: Vindi WooCommerce
 * Plugin URI: #!
 * Description: vindi payment gateway for WooCommerce.
 * Author: vindi
 * Author URI: #!
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: vindi-woocommerce
 * Domain Path: ./app/languages/
 */
	global $path;

if ( ! defined( 'ABSPATH' ) || class_exists( 'WC_Vindi_Payment' )  ) {
	exit;
}

$path = plugin_dir_path( __FILE__ ) . 'app/';

/**
 * WooCommerce Vindi Require Main Class.
*/

require_once 'app/vindi-woocommerce.php';