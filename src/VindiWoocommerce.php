<?php
namespace VindiPaymentGateways;

require_once plugin_dir_path(__FILE__)  . '/utils/AbstractInstance.php';


/**
 * @SuppressWarnings(PHPMD)
 */
class WcVindiPayment extends AbstractInstance
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
   * @var VindiPaymentGateway\VindiLanguages
   */
  private $languages;

  /**
   * @var VindiPaymentGateway\VindiSettings
   */
  private $settings;

  /**
   * @var VindiPaymentGateway\VindiControllers
   */
  private $controllers;

  /**
   * @var VindiPaymentGateway\VindiWebhooks
   */
  private $webhooks;

  /**
   * @var VindiPaymentGateway\FrontendFilesLoader
   */
  private $frontend_files_loader;

  /**
   * @var VindiPaymentGateway\VindiSubscriptionStatusHandler
   */
  private $subscription_status_handler;

  /**
   * @var VindiPaymentGateway\ProductsMetabox
   */
    private $product_metabox;

  /**
   * @var VindiPaymentGateway\VindiProductStatus
   */
    private $vindi_status_notifier;

  /**
   * @var VindiPaymentGateway\InterestPriceHandler
   */
    private $interest_price_handler;

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
    $this->vindi_status_notifier = new VindiProductStatus($this->settings);
    $this->interest_price_handler = new InterestPriceHandler();
        $this->product_metabox = new ProductsMetabox();

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

        add_filter('woocommerce_cart_needs_payment', [$this, 'filter_woocommerce_cart_needs_payment'], 10, 2);
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
        require_once plugin_dir_path(__FILE__) . '/services/Api.php';
        require_once plugin_dir_path(__FILE__) . '/services/Logger.php';
        require_once plugin_dir_path(__FILE__) . '/i18n/Languages.php';
        require_once plugin_dir_path(__FILE__) . '/services/VindiHelpers.php';
        require_once plugin_dir_path(__FILE__) . '/services/Webhooks.php';

        // Loading Abstract Method and Utils
        require_once plugin_dir_path(__FILE__) . '/utils/PaymentGateway.php';
        require_once plugin_dir_path(__FILE__) . '/utils/Conversions.php';
        require_once plugin_dir_path(__FILE__) . '/utils/RedirectCheckout.php';

        require_once plugin_dir_path(__FILE__) . '/includes/admin/CouponsMetaBox.php';
            require_once plugin_dir_path(__FILE__) . '/includes/admin/ProductsMetabox.php';
        require_once plugin_dir_path(__FILE__) . '/includes/admin/Settings.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/CreditPayment.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/BankSlipPayment.php';
        require_once plugin_dir_path(__FILE__) . '/utils/SubscriptionStatusHandler.php';
        require_once plugin_dir_path(__FILE__) . '/utils/InterestPriceHandler.php';

        require_once plugin_dir_path(__FILE__) . '/includes/admin/ProductStatus.php';

        // Routes import
        require_once plugin_dir_path(__FILE__) . '/routes/RoutesApi.php';

        // Controllers
        require_once plugin_dir_path(__FILE__) . '/controllers/index.php';

        require_once plugin_dir_path(__FILE__) . '/utils/PaymentProcessor.php';
        require_once plugin_dir_path(__FILE__) . '/utils/PostMeta.php';
  }

  public static function getPath()
  {
    return plugin_dir_path(__FILE__);
  }

  public static function get_instance()
  {
        require_once plugin_dir_path(__FILE__) . '/utils/FrontendFilesLoader.php';
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

  /**
   * Sobrescreve o método que remove os métodos de pagamento para assinaturas com trial
   * @return bool
   */
    public function filter_woocommerce_cart_needs_payment()
    {
        return true;
    }
}

add_action('plugins_loaded', array(WcVindiPayment::class, 'get_instance'));
