<?php

define('VINDI_VERSION', '1.0.0');

define('VINDI_MININUM_WP_VERSION', '5.0');
define('VINDI_MININUM_PHP_VERSION', '5.6');

define('VINDI', 'vindi-woocommerce');

define('VINDI__FILE__', dirname(dirname(__FILE__)));
define('VINDI_PLUGIN_BASE', plugin_basename(VINDI__FILE__));
define('VINDI_PATH', plugin_dir_path(VINDI__FILE__));

define('VINDI_SRC', plugin_dir_path(VINDI__FILE__) . '/src/');


if (defined('VINDI_TESTS') && VINDI_TESTS) {
  define('VINDI_URL', 'file://' . VINDI_PATH);
} else {
  define('VINDI_URL', plugins_url('/', VINDI__FILE__));
}

define('PREFIX_PRODUCT', '[ WC-Produto ]');

define('PREFIX_PLAN', '[ WC-Plano ]');
