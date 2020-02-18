<?php

require_once plugin_dir_path(__FILE__) . 'utils/AbstractInstance.php';

class WC_Vindi_Payment extends AbstractInstance
{
  /**
   * @var string
   */
  const TEMPLATE_DIR = '/templates/';

  /**
   * Instance of this class.
   *
   * @var object
   */

  static $instance = null;

  public function __construct()
  {

    // Checks if Woocommerce is installed and activated
    if (class_exists('WC_Payment_Gateway') && class_exists('Extra_Checkout_Fields_For_Brazil')) {

      $this->init();


      $this->languages = new VindiLanguages();
      $this->supports = new SupportSubscriptions();
      $this->settings = new VindiSettings();


      /**
       * Add Gateway to Woocommerce
       */
      add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
    } else {

      add_action('admin_notices', array($this, 'dependencies_notices'));
    }
  }

  /**
   * Init the plugin after plugins_loaded so environment variables are set.
   *
   * @since 1.0.0
   * @version 1.0.0
   */
  public function init()
  {
    require_once $this->getPath() . '/helpers/VindiHelpers.php';

    require_once $this->getPath() . '/includes/Languages.php';
    require_once $this->getPath() . '/includes/admin/Settings.php';
    require_once $this->getPath() . '/SupportSubscriptions.php';
  }

  /**
   * Dependencies notices.
   */

  public function dependencies_notices()
  {
    if (!class_exists('WC_Payment_Gateway')) {
      include_once 'views/woocommerce-missing.php';

      deactivate_plugins(VINDI_PATH . '/' . PLUGIN_FILE, true);
    }

    if (!class_exists('Extra_Checkout_Fields_For_Brazil')) {
      include_once 'views/ecfb-missing.php';

      deactivate_plugins(VINDI_PATH . '/' . PLUGIN_FILE, true);
    }
  }

  /**
   * Add the gateway to WooCommerce.
   *
   * @param  array $methods WooCommerce payment methods.
   *
   * @return array Payment methods with Vindi.
   */

  public function add_gateway($methods)
  {

    $methods[] = 'WC_Vindi_Credit_Gateway';

    return $methods;
  }

  public static function getPath()
  {
    return plugin_dir_path(__FILE__);
  }

  public static function get_instance()
  {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self;
    }

    return self::$instance;
  }
}

add_action('plugins_loaded', array('WC_Vindi_Payment', 'get_instance'));
