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
    add_filter('woocommerce_update_cart_validation', [$this, 'limit_duplicate_subscriptions_in_cart_update'], 10, 4);
    add_filter('woocommerce_add_to_cart_validation', [$this, 'disallow_subscription_with_single_product_in_cart'], 10, 4);
    add_filter('woocommerce_cart_needs_payment', [$this, 'filter_woocommerce_cart_needs_payment'], 10, 2);
    add_action('wp_ajax_renew_pix_charge', [$this, 'renew_pix_charge']);
    add_action('wp_ajax_nopriv_renew_pix_charge', [$this, 'renew_pix_charge']);
    do_action('woocommerce_set_cart_cookies',  true);
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
    require_once plugin_dir_path(__FILE__) . '/includes/gateways/PixPayment.php';
    require_once plugin_dir_path(__FILE__) . '/includes/gateways/BolepixPayment.php';
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
    $methods[] = new VindiPixGateway($this->settings, $this->controllers);
    $methods[] = new VindiBolepixGateway($this->settings, $this->controllers);

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
      if (
        class_exists('WC_Subscriptions_Product')
        && WC_Subscriptions_Product::get_trial_length($item['product_id']) > 0
      ) {
        return true;
      }
    }

    return false;
  }

  public function renew_pix_charge()
  {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $charge_id = filter_input(INPUT_POST, 'charge_id', FILTER_SANITIZE_NUMBER_INT);
    $subscription_id = filter_input(INPUT_POST, 'subscription_id', FILTER_SANITIZE_NUMBER_INT);

    $order = wc_get_order($order_id);
    $vindi_order = $order->get_meta('vindi_order', true);

    if ($charge_id && $subscription_id) {
      $routes = new VindiRoutes($this->settings);
      $charge = $routes->renewCharge($charge_id);

      if (isset($charge['status']) && isset($charge['last_transaction']['gateway_response_fields'])) {
        $last_transaction = $charge['last_transaction']['gateway_response_fields'];

        $subscription = $vindi_order[$subscription_id];
        $bill = [
          'id' => $subscription['bill']['id'],
          'status' => $subscription['bill']['status'],
          'charge_id' => $charge['id'],
          'pix_expiration' => $last_transaction['max_days_to_keep_waiting_payment'],
          'pix_code' => $last_transaction['qrcode_original_path'],
          'pix_qr' => $last_transaction['qrcode_path'],
        ];

        $vindi_order[$subscription_id]['bill'] = $bill;
        $order->update_meta_data('vindi_order', $vindi_order);
        $order->save();
      }
    }
  }

  function limit_same_subscriptions($passed, $product_id, $quantity)
  {
      $product = wc_get_product($product_id);
  
      if ($product->is_virtual()) {
          return $passed;
      }
  
      if (WC_Subscriptions_Product::is_subscription($product_id)) {
          $subscription_count = $this->get_subscription_count($product_id);
  
          if ($subscription_count + $quantity > 1) {
              wc_add_notice('Você só pode ter até 1 assinatura do mesmo produto no seu carrinho.', 'error');
              return false;
          }
      }
  
      return $passed;
  }
  
  function get_subscription_count($product_id)
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

  function limit_duplicate_subscriptions_in_cart_update($passed, $cart_item_key, $values, $quantity)
  {
      $product_id = $values['product_id'];
      $product = wc_get_product($product_id);
  
      if ($this->is_virtual_or_subscription_exceeds_limit($product, $product_id, $quantity)) {
          return false;
      }
  
      return $passed;
  }
  
  function is_virtual_or_subscription_exceeds_limit($product, $product_id, $quantity)
  {
      if ($product->is_virtual()) {
          return false;
      }
  
      if (WC_Subscriptions_Product::is_subscription($product_id)) {
          $subscription_count = 0;
          foreach (WC()->cart->get_cart() as $cart_item) {
              if ($cart_item['data']->get_id() === $product_id) {
                  $subscription_count++;
              }
          }
  
          if ($subscription_count >= 1 && $quantity > 1) {
              wc_add_notice(__('Você só pode ter até 1 assinatura do mesmo produto no seu carrinho.', 'vindi-payment-gateway'), 'error');
              return true;
          }
      }
  
      return false;
  }

  function disallow_subscription_with_single_product_in_cart($passed, $product_id, $quantity)
  {
      $product = wc_get_product($product_id);
  
      if ($product->is_virtual()) {
          return $passed;
      }
  
      if ($this->is_cart_mixed_with_subscription($product_id)) {
          wc_add_notice(__('Olá! Finalize a compra da assinatura adicionada ao carrinho antes de adicionar outra assinatura ou produto.', 'vindi-payment-gateway'), 'error');
          return false;
      }
  
      return $passed;
  }
  
  function is_cart_mixed_with_subscription($product_id)
  {
      $cart = WC()->cart->get_cart();
      if (empty($cart)) {
          return false;
      }
  
      $is_subscription = false;
      $new_product_subscription = WC_Subscriptions_Product::is_subscription($product_id);
  
      foreach ($cart as $cart_item) {
          if (WC_Subscriptions_Product::is_subscription($cart_item['data']->get_id())) {
              $is_subscription = true;
              break;
          }
      }
  
      return $is_subscription !== $new_product_subscription;
  }
}


add_action('plugins_loaded', array(WcVindiPayment::class, 'get_instance'));
