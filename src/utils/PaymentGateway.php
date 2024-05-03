<?php

namespace VindiPaymentGateways;

use WC_Payment_Gateway_CC;
use Exception;
use WC_Order;
use WP_Error;

if (!defined('ABSPATH')) {
  exit;
}

include_once VINDI_PATH . 'src/services/VindiHelpers.php';

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 1.0.0
 */

abstract class VindiPaymentGateway extends WC_Payment_Gateway_CC
{
  /**
   * @var bool
   */
  protected $validated = true;

  /**
   * @var VindiSettings
   */
  public $vindi_settings;

  /**
   * @var VindiControllers
   */
  public $controllers;

  /**
   * @var VindiLogger
   */
  protected $logger;

  /**
   * @var VindiRoutes
   */
  protected $routes;

  /**
   * Should return payment type for payment processing.
   * @return string
   */
  public abstract function type();

  public function __construct(VindiSettings $vindi_settings, VindiControllers $controllers)
  {
    $this->vindi_settings = $vindi_settings;
    $this->controllers = $controllers;
    $this->logger = $this->vindi_settings->logger;
    $this->routes = $vindi_settings->routes;
    $this->title = $this->get_option('title');
    $this->enabled = $this->get_option('enabled');

    if (is_admin()) {
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
    }
  }

  /**
   * Create the level 3 data array to send to Vindi when making a purchase.
   *
   * @param WC_Order $order The order that is being paid for.
   * @return array          The level 3 data to send to Vindi.
   */
  public function get_level3_data_from_order($order)
  {
    // WC Versions before 3.0 don't support postcodes and are
    // incompatible with level3 data.
    if (VindiHelpers::is_wc_lt('3.0')) {
      return array();
    }

    // Get the order items. Don't need their keys, only their values.
    // Order item IDs are used as keys in the original order items array.
    $order_items = array_values($order->get_items());
    $currency    = $order->get_currency();

    $vindi_line_items = array_map(function ($item) use ($currency) {
      $product_id          = $item->get_variation_id()
        ? $item->get_variation_id()
        : $item->get_product_id();
      $product_description = substr($item->get_name(), 0, 26);
      $quantity            = $item->get_quantity();
      $unit_cost           = VindiHelpers::get_vindi_amount(($item->get_subtotal() / $quantity), $currency);
      $tax_amount          = VindiHelpers::get_vindi_amount($item->get_total_tax(), $currency);
      $discount_amount     = VindiHelpers::get_vindi_amount($item->get_subtotal() - $item->get_total(), $currency);

      return (object) array(
        'product_code'        => (string) $product_id, // Up to 12 characters that uniquely identify the product.
        'product_description' => $product_description, // Up to 26 characters long describing the product.
        'unit_cost'           => $unit_cost, // Cost of the product, in cents, as a non-negative integer.
        'quantity'            => $quantity, // The number of items of this type sold, as a non-negative integer.
        'tax_amount'          => $tax_amount, // The amount of tax this item had added to it, in cents, as a non-negative integer.
        'discount_amount'     => $discount_amount, // The amount an item was discounted—if there was a sale,for example, as a non-negative integer.
      );
    }, $order_items);

    $level3_data = array(
      'merchant_reference'   => $order->get_id(), // An alphanumeric string of up to  characters in length. This unique value is assigned by the merchant to identify the order. Also known as an “Order ID”.

      'shipping_amount'      => VindiHelpers::get_vindi_amount($order->get_shipping_total() + $order->get_shipping_tax(), $currency), // The shipping cost, in cents, as a non-negative integer.
      'line_items'           => $vindi_line_items,
    );

    $shipping_address_zip = $order->get_shipping_postcode();

    if ($this->is_valid_br_zip_code($shipping_address_zip)) {

      $level3_data['shipping_address_zip'] = $shipping_address_zip;
    }

    $store_postcode = get_option('woocommerce_store_postcode');
    if ($this->is_valid_br_zip_code($store_postcode)) {
      $level3_data['shipping_from_zip'] = $store_postcode;
    }

    return $level3_data;
  }

  /**
   * Verifies whether a certain ZIP code is valid for the BRL, incl. 8-digit extensions.
   *
   * @param string $zip The ZIP code to verify.
   * @return boolean
   */
  public function is_valid_br_zip_code($zip)
  {
    return !empty($zip) && preg_match('/^[0-9]{5,5}([- ]?[0-9]{3,3})?$/', $zip);
  }

  /**
   * Admin Panel Options
   */
  public function admin_options()
  {
      $this->vindi_settings->get_template('admin-gateway-settings.html.php', array('gateway' => $this));
  }

  /**
   * Get the users country either from their order, or from their customer data
   * @return string|null
   */
  public function get_country_code()
  {
    if (isset($_GET['order_id'])) {
      $order = new WC_Order(filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT));
      return $order->billing_country;
    } elseif ($this->vindi_settings->woocommerce->customer->get_billing_country()) {
      return $this->vindi_settings->woocommerce->customer->get_billing_country();
    }
  }

  /**
   * Validate plugin settings
   * @return bool
   */
  public function validate_settings()
  {
    $currency = get_option('woocommerce_currency');
    $api_key = $this->vindi_settings->get_api_key();
    return in_array($currency, ['BRL']) && ! empty($api_key);
  }

  /**
   * Process the payment
   *
   * @param int $order_id
   *
   * @return array
   */
  public function process_payment($order_id)
  {
    $order_id = filter_var($order_id, FILTER_SANITIZE_NUMBER_INT);
    $this->logger->log(sprintf('Processando pedido %s.', $order_id));
    $order   = wc_get_order($order_id);
    $payment = new VindiPaymentProcessor($order, $this, $this->vindi_settings, $this->controllers);

    // exit if validation by validate_fields() fails
    if (! $this->validated) {
      return false;
    }

    // Validate plugin settings
    if (! $this->validate_settings()) {
      return $payment->abort(__('O Pagamento foi cancelado devido a erro de configuração do meio de pagamento.', VINDI));
    }

    try {
      $response = $payment->process();
      $order->reduce_order_stock();
    } catch (Exception $e) {
      $response = array(
        'result'   => 'fail',
        'redirect' => '',
      );
    }

    return $response;
  }

  /**
   * Check if the order is a Single Payment Order (not a Subscription).
   * @return bool
   */
  protected function is_single_order()
  {
    $types = [];

    foreach ($this->vindi_settings->woocommerce->cart->cart_contents as $item) {
      $types[] = $item['data']->get_type();
    }

    return !(boolean) preg_grep('/subscription/', $types);
  }

  /**
   * Process a refund.
   *
   * @param  int    $order_id Order ID.
   * @param  float  $amount Refund amount.
   * @param  string $reason Refund reason.
   * @return bool|WP_Error
   */
  public function process_refund($order_id, $amount = null, $reason = '') {
    $order_id = filter_var($order_id, FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT);
    $reason = sanitize_text_field($reason);
    $order = wc_get_order($order_id);

    if (!$this->can_refund_order($order)) {
      return new WP_Error('error', __('Reembolso falhou.', VINDI));
    }

    if($amount < $order->get_total()) {
      return new WP_Error('error', __('Não é possível realizar reembolsos parciais, faça um reembolso manual caso você opte por esta opção.', VINDI));
    }

    $results = [];
        $order_meta = $order->get_meta('vindi_order', true);
    foreach ($order_meta as $key => $order_item) {
      $bill_id = $order_item['bill']['id'];

      $result = $this->refund_transaction($bill_id, $amount, $reason);

      $this->logger->log('Resultado do reembolso: ' . wc_print_r($result, true));
      switch (strtolower($result['status'])) {
        case 'success':
          $order->add_order_note(
            /* translators: 1: Refund amount, 2: Refund ID */
            sprintf(__('[Transação #%2$s]: reembolsado R$%1$s', VINDI), $result['amount'], $result['id'])
          );
          break;
      }
      if(isset($result->errors)) {
        throw new Exception($result->errors[0]->message);
        return false;
      }
    }
    return true;
  }

  /**
	 * Get refund request args.
	 *
	 * @param  int      $bill_id Order object.
	 * @param  float    $amount Refund amount.
	 * @param  string   $reason Refund reason.
	 * @return array
	 */
	public function get_refund_request($bill_id, $amount = null, $reason = '') {
    $bill_id = filter_var($bill_id, FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT);
    $reason = sanitize_text_field($reason);
		$request = array(
            'cancel_bill' => true,
            'comments' => strip_tags(wc_trim_string($reason, 255)),
            'amount' => null
		);
		if (!is_null($amount) ) {
			$request['amount'] = substr($amount, 0, strlen($amount)-2) . "." . substr($amount, -2);
		}
		return apply_filters('vindi_refund_request', $request, $bill_id, $request['amount'], $reason);
  }

  /**
	 * Refund an order via PayPal.
	 *
	 * @param  int      $bill_id Order object.
	 * @param  float    $amount Refund amount.
	 * @param  string   $reason Refund reason.
	 * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
	 */
	public function refund_transaction($bill_id, $amount = null, $reason = '') {
    $bill_id = filter_var($bill_id, FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT);
    $reason = sanitize_text_field($reason);

    $data = $this->get_refund_request($bill_id, $amount, $reason);
    $last_charge = $this->find_bill_last_charge($bill_id);
    $charge_id = $last_charge['id'];
    $refund = $this->routes->refundCharge($charge_id, $data);

		if (empty($refund)) {
            return new WP_Error('error', __('Reembolso na Vindi falhou. Verifique o log do plugin', VINDI));
		}

		return $refund['last_transaction'];
  }

  private function find_bill_last_charge($bill_id)
  {
    $bill_id = filter_var($bill_id, FILTER_SANITIZE_NUMBER_INT);
    $bill = $this->routes->findBillById($bill_id);

    if(!$bill) {
      throw new Exception(sprintf(__('A fatura com bill_id #%s não foi encontrada!', VINDI), $bill_id), 2);
    }

    $charges = $bill['charges'];
    return end($charges);
	}
};
