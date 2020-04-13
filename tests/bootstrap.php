<?php

/**
 * Vindi Unit Tests Bootstrap
 *
 * @since 1.0
 * @package Vindi Tests
 */

/**
 * Define error OFF
 */
ini_set('display_errors', 'Off');
ini_set('error_reporting', E_ALL);
define('WP_DEBUG_DISPLAY', false);


/**
 * Class Vindi_Unit_Tests_Bootstrap
 */

define('VINDI_TESTS', true);
/**
 * change PLUGIN_FILE env in phpunit.xml
 */
define('PLUGIN_FILE', getenv('PLUGIN_FILE'));
define('PLUGIN_FOLDER', basename(dirname(__DIR__)));
define('PLUGIN_PATH', PLUGIN_FOLDER . '/' . PLUGIN_FILE);

class Vindi_Unit_Tests_Bootstrap
{

  // /** @var Vindi_Unit_Tests_Bootstrap instance */
  protected static $instance = null;

  /** @var string testing directory */
  public $_tests_dir;

  /** @var string plugin directory */
  public $plugin_dir;

  /**
   * Setup the unit testing environment.
   *
   * @since 1.0
   */
  public function __construct()
  {

    // ini_set('display_errors', 'on');
    // error_reporting(E_ALL);


    if (!isset($_SERVER['SERVER_NAME'])) {
      $_SERVER['SERVER_NAME'] = 'localhost';
    }



    $this->tests_dir    = dirname(__FILE__);
    $this->plugin_dir   = dirname($this->tests_dir);
    $this->wp_tests_dir = getenv('WP_TESTS_DIR') ? getenv('WP_TESTS_DIR') : '/tmp/wordpress-tests-lib';


    // load test function so tests_add_filter() is available.
    require_once $this->wp_tests_dir . '/includes/functions.php';

    // load Vindi.
    function _manually_load_plugin()
    {

      require '/tmp/woocommerce/woocommerce.php';
      require dirname(__DIR__) . '/' . PLUGIN_FILE;
    }

    tests_add_filter('muplugins_loaded', '_manually_load_plugin');

    // load the WP testing environment.
    require_once $this->wp_tests_dir . '/includes/bootstrap.php';

    // load Vindi testing framework.
    $this->includes_wc();
    $this->includes();

    // Removes all sql tables on shutdown
    // Do this action last
    tests_add_filter('shutdown', 'drop_tables', 999999);
  }

  /**
   * Load Vindi-specific test cases and factories.
   *
   * @since 1.0
   */
  public function includes()
  {

    // Tests File.
    require_once $this->tests_dir . '/phpunit/local-factory.php';
    require_once $this->tests_dir . '/phpunit/trait-test-base.php';
    require_once $this->tests_dir . '/phpunit/base-class.php';
    require_once $this->tests_dir . '/phpunit/ajax-class.php';
    require_once $this->tests_dir . '/phpunit/manager.php';
  }

  public function includes_wc()
  {
    $wc_tests_framework_base_dir = '/tmp/woocommerce/tests/framework/';
    $wc_tests_includes_base_dir  = '/tmp/woocommerce/';

    // framework
    require_once $wc_tests_framework_base_dir . 'class-wc-unit-test-factory.php';
    require_once $wc_tests_framework_base_dir . 'class-wc-mock-session-handler.php';
    require_once $wc_tests_framework_base_dir . 'class-wc-mock-wc-data.php';
    require_once $wc_tests_framework_base_dir . 'class-wc-mock-wc-object-query.php';
    require_once $wc_tests_framework_base_dir . 'class-wc-mock-payment-gateway.php';
    require_once $wc_tests_framework_base_dir . 'class-wc-payment-token-stub.php';
    require_once $wc_tests_framework_base_dir . 'vendor/class-wp-test-spy-rest-server.php';


    // // test case
    // require_once $wc_tests_framework_base_dir . 'class-wc-unit-test-case.php';

    // Helpers
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-product.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-coupon.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-fee.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-shipping.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-customer.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-order.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-shipping-zones.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-payment-token.php';
    require_once $wc_tests_framework_base_dir . 'helpers/class-wc-helper-settings.php';
  }

  /**
   * Get the single class instance.
   *
   * @since 1.0
   * @return Vindi_Unit_Tests_Bootstrap
   */
  public static function instance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;
  }
}

Vindi_Unit_Tests_Bootstrap::instance();
