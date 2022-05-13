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
  const WC_API_CALLBACK = 'vindi_webhook';

  /**
   * Instance of this class.
   *
   * @var object
   */
  static $instance = null;

  /**
   * @var VindiLanguages
   */
  private $languages;

  /**
   * @var VindiSettings
   */
  private $settings;

  /**
   * @var VindiControllers
   */
  private $controllers;

  /**
   * @var VindiWebhooks
   */
  private $webhooks;

  /**
   * @var FrontendFilesLoader
   */
  private $frontend_files_loader;

  /**
   * @var VindiSubscriptionStatusHandler
   */
  private $subscription_status_handler;

  /**
   * @var VindiSubscriptionItemsHandler
   */
  private $subscription_items_handler;

  public function __construct()
  {

    // Checks if Woocommerce is installed and activated
    $this->init();


    $this->languages = new VindiLanguages();

    $this->settings = new VindiSettings();
    $this->controllers = new VindiControllers($this->settings);
    $this->webhooks = new VindiWebhooks($this->settings);
    $this->frontend_files_loader = new FrontendFilesLoader();
    $this->subscription_status_handler = new VindiSubscriptionStatusHandler($this->settings);
    $this->subscription_items_handler = new VindiSubscriptionItemsHandler($this->settings);
    $this->vindi_status_notifier = new VindiProductStatus($this->settings);
    $this->interest_price_handler = new InterestPriceHandler();

    /**
      * Add Gateway to Woocommerce
      */
    add_filter('woocommerce_payment_gateways', array(&$this, 'add_gateway'));

    /**
      * Register webhook handler
      */
    add_action('woocommerce_api_' . self::WC_API_CALLBACK, array(
      $this->webhooks, 'handle'
    ));
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
    require_once $this->getPath() . '/services/Webhooks.php';

    // Loading Abstract Method and Utils
    require_once $this->getPath() . '/utils/PaymentGateway.php';
    require_once $this->getPath() . '/utils/Conversions.php';
    require_once $this->getPath() . '/utils/RedirectCheckout.php';

    require_once $this->getPath() . '/includes/admin/CouponsMetaBox.php';
    require_once $this->getPath() . '/includes/admin/Settings.php';
    require_once $this->getPath() . '/includes/gateways/CreditPayment.php';
    require_once $this->getPath() . '/includes/gateways/BankSlipPayment.php';
    require_once $this->getPath() . '/utils/SubscriptionStatusHandler.php';
    require_once $this->getPath() . '/utils/SubscriptionItemsHandler.php';
    require_once $this->getPath() . '/utils/InterestPriceHandler.php';

    require_once $this->getPath() . '/includes/admin/ProductStatus.php';

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
    require_once self::getPath() . '/utils/FrontendFilesLoader.php';
    new FrontendFilesLoader();

    if (VindiDependencies::check()) {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance) {
        self::$instance = new self;
      }

      return self::$instance;
    }
  }

  /**
   * Add the gateway to WooCommerce.
   * @param  array $methods WooCommerce payment methods.
   * @return array Payment methods with Vindi.
   */
  public function add_gateway($methods)
  {
    $methods[] = new VindiCreditGateway($this->settings, $this->controllers);
    $methods[] = new VindiBankSlipGateway($this->settings, $this->controllers);

    return $methods;
  }
}

add_action('plugins_loaded', array('WC_Vindi_Payment', 'get_instance'));
