<?php

define('VINDI_VERSION', '1.3.4');

define('VINDI_MININUM_WP_VERSION', '5.0');
define('VINDI_MININUM_PHP_VERSION', '5.6');

define('VINDI', 'vindi-payment-gateway');

define('VINDI_FILE', dirname(dirname(__FILE__)));
define('VINDI_PLUGIN_BASE', plugin_basename(VINDI_FILE));
define('VINDI_PATH', plugin_dir_path(VINDI_FILE));

define('VINDI_SRC', plugin_dir_path(VINDI_FILE) . '/src/');


if (defined('VINDI_TESTS') && VINDI_TESTS) {
  define('VINDI_URL', 'file://' . VINDI_PATH);
} else {
  define('VINDI_URL', plugins_url('/', VINDI_FILE));
}

define('VINDI_PREFIX_PRODUCT', '[WC] ');

define('VINDI_PREFIX_PLAN', '[WC] ');
