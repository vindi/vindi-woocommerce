<?php

/**
 * Vindi Unit Tests Bootstrap
 *
 * @since 1.0
 * @package Vindi Tests
 */

/**
 * Class Vindi_Unit_Tests_Bootstrap
 */

define('Vindi_TESTS', true);
/**
 * change PLUGIN_FILE env in phpunit.xml
 */
define('PLUGIN_FILE', getenv('PLUGIN_FILE'));
define('PLUGIN_FOLDER', basename(dirname(__DIR__)));
define('PLUGIN_PATH', PLUGIN_FOLDER . '/' . PLUGIN_FILE);
class Vindi_Unit_Tests_Bootstrap
{

  /** @var Vindi_Unit_Tests_Bootstrap instance */
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

    ini_set('display_errors', 'on');
    error_reporting(E_ALL);


    if (!isset($_SERVER['SERVER_NAME'])) {
      $_SERVER['SERVER_NAME'] = 'localhost';
    }

    $this->tests_dir    = dirname(__FILE__);
    $this->plugin_dir   = dirname($this->tests_dir);
    $this->wp_tests_dir = getenv('WP_TESTS_DIR') ? getenv('WP_TESTS_DIR') : '/tmp/wordpress-tests-lib';


    // load test function so tests_add_filter() is available.
    require_once $this->wp_tests_dir . '/includes/functions.php';


    // load Vindi.
    tests_add_filter('muplugins_loaded', function () {
      // Manually load plugin
      require dirname(__DIR__) . '/' . PLUGIN_FILE;
    });

    // Activates this plugin in WordPress so it can be tested.
    $GLOBALS['wp_tests_options'] = [
      'active_plugins' => [PLUGIN_PATH],
      'template' => 'twentysixteen',
      'stylesheet' => 'twentysixteen',
    ];

    // Removes all sql tables on shutdown
    // Do this action last
    tests_add_filter('shutdown', 'drop_tables', 999999);

    // load the WP testing environment.
    require_once $this->wp_tests_dir . '/includes/bootstrap.php';


    // load Vindi testing framework.
    $this->includes();
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
