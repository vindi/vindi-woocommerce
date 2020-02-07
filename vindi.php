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
 *
 * Domain Path: ./src/languages/
 *
 * @package Vindi
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('VINDI_VERSION', '1.0.0');

define('VINDI_MININUM_WP_VERSION', '5.0');
define('VINDI_MININUM_PHP_VERSION', '5.6');

define('VINDI', 'vindi-woocommerce');

define('VINDI__FILE__', __FILE__);
define('VINDI_PLUGIN_BASE', plugin_basename(VINDI__FILE__));
define('VINDI_PATH', plugin_dir_path(VINDI__FILE__));

// define('VINDI_ASSETS_PATH', VINDI_PATH . 'src/assets/');
// define('VINDI_ASSETS_URL', URL_URL . 'src/assets/');

if (defined('VINDI_TESTS') && VINDI_TESTS) {
    define('VINDI_URL', 'file://' . VINDI_PATH);
} else {
    define('VINDI_URL', plugins_url('/', VINDI__FILE__));
}

if (!version_compare(PHP_VERSION, VINDI_MININUM_PHP_VERSION, '>=')) {
    add_action('admin_notices', 'vindi_fail_php_version');

} elseif (!version_compare(get_bloginfo('version'), VINDI_MININUM_WP_VERSION, '>=')) {
    add_action('admin_notices', 'vindi_fail_wp_version');

} else {

    require VINDI_PATH . 'src/vindi-woocommerce.php';

}

/**
 * Vindi admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vindi_fail_php_version()
{
    include_once VINDI_PATH . 'src/views/php-version-missing.php';
}

/**
 * Vindi admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vindi_fail_wp_version()
{
    include_once VINDI_PATH . 'src/views/wp-version-missing.php';
}
