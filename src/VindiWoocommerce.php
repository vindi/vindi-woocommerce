<?php
require_once VINDI_SRC . '/utils/AbstractInstance.php';

class WC_Vindi_Payment extends AbstractInstance
{
  /**
   * @var string
   */
  const TEMPLATE_DIR = '/templates/';

  /**
   * @var string
   */
  const MODE = 'development';

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


      $this->languages   = new VindiLanguages();

      $this->settings    = new VindiSettings();
      $this->controllers = new VindiControllers($this->settings);
      $this->frontendLoader    = new FrontendFilesLoader();


      /**
       * Add Gateway to Woocommerce
       */
      add_filter('woocommerce_payment_gateways', array($this->settings, 'add_gateway'));
    } else {

      add_action('admin_notices', 'dependencies_notices');
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
    // Helpers and Languages
    require_once $this->getPath() . '/services/Api.php';
    require_once $this->getPath() . '/services/Logger.php';
    require_once $this->getPath() . '/i18n/Languages.php';
    require_once $this->getPath() . '/services/VindiHelpers.php';

    // Loading Abstract Method and Utils
    require_once $this->getPath() . '/utils/PaymentGateway.php';
    require_once $this->getPath() . '/utils/Conversions.php';

    require_once $this->getPath() . '/includes/admin/CouponsMetaBox.php';
    require_once $this->getPath() . '/includes/admin/Settings.php';
    require_once $this->getPath() . '/includes/gateways/CreditPayment.php';
    require_once $this->getPath() . '/includes/gateways/BankSlipPayment.php';
    require_once $this->getPath() . '/includes/FrontendFilesLoader.php';

    // Routes import
    require_once $this->getPath() . '/routes/RoutesApi.php';

    // Controllers
    require_once $this->getPath() . '/controllers/index.php';

    require_once $this->getPath() . '/utils/PaymentProcessor.php';
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
