<?php

/**
 * Vindi admin notice for WC and ECFB required.
 *
 * Warning when the site doesn't have the for WC and ECFB required.
 *
 * @since 1.0.0
 *
 * @return void
 */
function dependencies_notices()
{
  if (!class_exists('WC_Payment_Gateway')) {
    include_once VINDI_SRC . 'views/woocommerce-missing.php';

    deactivate_plugins(VINDI_PATH . '/' . VINDI_FILE, true);
  }

  if (!class_exists('Extra_Checkout_Fields_For_Brazil')) {
    include_once VINDI_SRC . 'views/ecfb-missing.php';

    deactivate_plugins(VINDI_PATH . '/' . VINDI_FILE, true);
  }
};

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
  include_once VINDI_SRC . 'views/php-version-missing.php';
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
  include_once VINDI_SRC . 'views/wp-version-missing.php';
}
