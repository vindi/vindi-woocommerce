<?php
namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

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

  /**
  * @var VindiWCSRenewalDisable
  */
    private $wcs_renewal_disable;

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
        $this->wcs_renewal_disable = new VindiWCSRenewalDisable();


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
   
        add_filter('woocommerce_add_to_cart_validation', [$this, 'limit_same_subscriptions'], 10, 3);
        add_filter('woocommerce_update_cart_validation', [$this, 'limit_subscriptions_in_cart_update'], 10, 4);
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
        require_once plugin_dir_path(__FILE__) . '/utils/PostMeta.php';

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
        require_once plugin_dir_path(__FILE__) . '/utils/WCSRenewalDisable.php';
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
    public function filter_woocommerce_cart_needs_payment($needs_payment, $cart)
    {
        if (floatval($cart->total) == 0 || $this->cart_has_trial($cart)) {
            return true;
        }

        return $needs_payment;
    }

    private function cart_has_trial($cart)
    {
        $items = $cart->get_cart();
        foreach ($items as $item) {
            if (class_exists('WC_Subscriptions_Product')
                && WC_Subscriptions_Product::get_trial_length($item['product_id']) > 0) {
                return true;
            }
        }

        return false;
    }

    public function count_subscriptions_in_cart($product_id)
    {
        $cart = WC()->cart->get_cart();
        $subscription_count = 0;
        foreach ($cart as $cart_item) {
            if ($cart_item['data']->get_id() === $product_id) {
                $subscription_count += $cart_item['quantity'];
            }
        }
        return $subscription_count;
    }
  
    public function limit_same_subscriptions($passed, $product_id, $quantity)
    {
        if (WC_Subscriptions_Product::is_subscription($product_id)) {
            $subscription_count = $this->count_subscriptions_in_cart($product_id);
            if ($subscription_count + $quantity > 1) {
                wc_add_notice(__('Você só pode ter até 1 assinaturas do mesmo produto no seu carrinho.'), 'error');
                return false;
            }
        }
        return $passed;
    }

    public function count_subscriptions_in_cart($product_id)
    {
        $cart = WC()->cart->get_cart();
        $subscription_count = 0;
        foreach ($cart as $cart_item) {
            if ($cart_item['data']->get_id() === $product_id) {
                $subscription_count++;
            }
        }
        return $subscription_count;
    }

    public function limit_subscriptions_in_cart_update($passed, $cart_item_key, $values, $quantity)
    {
        if (WC_Subscriptions_Product::is_subscription($values['product_id'])) {
            $subscription_count = $this->count_subscriptions_in_cart($values['product_id']);
            if ($subscription_count >= 1 && $quantity > 1) {
                wc_add_notice(__('Você só pode ter até 1 assinaturas do mesmo produto no seu carrinho.'), 'error');
                return false;
            }
        }
        return $passed;
    }
}


add_action('plugins_loaded', array(WcVindiPayment::class, 'get_instance'));
