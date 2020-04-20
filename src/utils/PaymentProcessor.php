<?php

class VindiPaymentProcessor
{
  /**
   * Order type is invalid.
   */
  const ORDER_TYPE_INVALID = 0;

  /**
   * Order type is Subscription Payment.
   */
  const ORDER_TYPE_SUBSCRIPTION = 1;

  /**
   * Order type is Single Payment.
   */
  const ORDER_TYPE_SINGLE = 2;

  /**
   * @var WC_Order
   */
  private $order;

  /**
   * @var VindiPaymentGateway
   */
  private $gateway;

  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  /**
   * @var VindiLogger
   */
  private $logger;

  /**
   * @var VindiRoutes
   */
  private $routes;

  function __construct(WC_Order $order, VindiPaymentGateway $gateway, VindiSettings $vindi_settings)
  {
    $this->order = $order;
    $this->gateway = $gateway;
    $this->vindi_settings = $vindi_settings;
    $this->logger = $vindi_settings->logger;
    $this->routes = new VindiRoutes($vindi_settings);
  }

  /**
   * Validate order to chose payment type.
   * @return int order type.
   */
  public function get_order_type()
  {
    $items = $this->order->get_items();

    foreach ($items as $item) {
      $product = $this->order->get_product_from_item($item);
      if ($this->is_subscription_type($product)) return static ::ORDER_TYPE_SUBSCRIPTION;
    }

    return static ::ORDER_TYPE_SINGLE;
  }

  /**
   * Retrieve Plan for Vindi Subscription.
   * @return int|bool
   */
  public function get_plan()
  {
    $items = $this->order->get_items();

    foreach ($items as $item) {
      $product = $this->order->get_product_from_item($item);

      if (isset($item['variation_id']) && $item['variation_id'] != 0) {
        $vindi_plan = get_post_meta($item['variation_id'], 'vindi_variable_subscription_plan', true);
        if (empty($vindi_plan) || !is_numeric($vindi_plan) || is_null($vindi_plan) || $vindi_plan == 0) {
          $vindi_plan = $product->get_meta('vindi_plan_id', true);
        }
      }
      else $vindi_plan = $product->get_meta('vindi_plan_id', true);

      if ($this->is_subscription_type($product) and !empty($vindi_plan)) return $vindi_plan;
    }

    $this->abort(__('O produto selecionado não é uma assinatura.', VINDI) , true);
  }

  /**
   * Find or Create a Customer at Vindi for the given credentials.
   * @return array|bool
   */
  public function get_customer()
  {
    $current_user = wp_get_current_user();
    $vindi_customer_id = get_user_meta($current_user->ID, 'vindi_customer_id')[0];
    $vindi_customer = $this->routes->findCustomerByid($vindi_customer_id);
    // TODO: if user doesn't exist, create

    if ($this->is_cc())
      $this->create_payment_profile($vindi_customer['id']);
      
    $this->logger->log(sprintf('Cliente Vindi: %s', $vindi_customer['id']));

    return $vindi_customer;
  }

  /**
   * Build payment type for credit card.
   *
   * @param int $customer_id
   *
   * @return array
   */
  public function get_cc_payment_type($customer_id)
  {
    if ($this->gateway->verify_user_payment_profile()) return false;

    return array(
      'customer_id' => $customer_id,
      'holder_name' => $_POST['vindi_cc_fullname'],
      'card_expiration' => $_POST['vindi_cc_monthexpiry'] . '/' . $_POST['vindi_cc_yearexpiry'],
      'card_number' => $_POST['vindi_cc_number'],
      'card_cvv' => $_POST['vindi_cc_cvc'],
      'payment_method_code' => $this->payment_method_code() ,
      'payment_company_code' => $_POST['vindi_cc_paymentcompany'],
    );
  }

  /**
   * Check if payment is of type "Credit Card"
   * @return bool
   */
  public function is_cc()
  {
    return 'cc' === $this->gateway->type();
  }

  /**
   * Check if payment is of type "bank_slip"
   * @return bool
   */
  public function is_bank_slip()
  {
    return 'bank_slip' === $this->gateway->type();
  }

  /**
   * @return string
   */
  public function payment_method_code()
  {
    // TODO fix it to proper method code
    return $this->is_cc() ? 'credit_card' : 'bank_slip';
  }

  /**
   * @param string $message
   * @param bool   $throw_exception
   *
   * @return bool
   * @throws Exception
   */
  public function abort($message, $throw_exception = false)
  {
    $this->logger->log($message);
    $this->order->add_order_note($message);
    wc_add_notice($message, 'error');
    if ($throw_exception) throw new Exception($message);

    return false;
  }

  /**
   * @return array|void
   * @throws Exception
   */
  public function process()
  {
    switch ($orderType = $this->get_order_type()) {
      case static ::ORDER_TYPE_SINGLE:
        return $this->process_single_payment();
      case static ::ORDER_TYPE_SUBSCRIPTION:
        return $this->process_subscription();
      case static ::ORDER_TYPE_INVALID:
      default:
        return $this->abort(__('Falha ao processar carrinho de compras. Verifique os itens escolhidos e tente novamente.', VINDI) , true);
    }
  }

  /**
   * @return array
   * @throws Exception
   */
  public function process_subscription()
  {
    $customer = $this->get_customer();
    $subscription = $this->create_subscription($customer['id']);
    $wc_subscriptions = wcs_get_subscriptions_for_order($this->order);
    $wc_subscription = end($wc_subscriptions);

    add_post_meta($this->order->id, 'vindi_wc_cycle', $subscription['current_period']['cycle']);
    add_post_meta($this->order->id, 'vindi_wc_subscription_id', $subscription['id']);
    add_post_meta($this->order->id, 'vindi_wc_bill_id', $subscription['bill']['id']);
    add_post_meta($wc_subscription->id, 'vindi_wc_subscription_id', $subscription['id']);

    if ($message = $this->cancel_if_denied_bill_status($subscription['bill'])) {
      $wc_subscription->update_status('cancelled', __($message, VINDI));
      $this->order->update_status('cancelled', __($message, VINDI));
      $this->routes->suspendSubscription($subscription['id'], true);
      $this->abort(__($message, VINDI) , true);
    }

    $this->add_download_url_meta_for_order($subscription, true);

    remove_action('woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::prepare_renewal');

    return $this->finish_payment($subscription['bill']);
  }

  /**
   * @return array
   * @throws Exception
   */
  public function process_single_payment()
  {
    $customer = $this->get_customer();
    $bill = $this->create_bill($customer['id']);

    if ($message = $this->cancel_if_denied_bill_status($bill)) {
      $this->routes->deleteBill($bill['id']);
      $this->order->update_status('cancelled', __($message, VINDI));
      $this->abort(__($message, VINDI) , true);
    }

    add_post_meta($this->order->id, 'vindi_wc_bill_id', $bill['id']);
    $this->add_download_url_meta_for_order($bill, false);

    return $this->finish_payment($bill);
  }

  /**
   * @param int $customer_id
   *
   * @throws Exception
   */
  protected function create_payment_profile($customer_id)
  {
    $cc_info = $this->get_cc_payment_type($customer_id);

    if (false === $cc_info)
      return;

    $payment_profile = $this->routes->createCustomerPaymentProfile($cc_info);

    if (!$payment_profile)
      $this->abort(__('Falha ao registrar o método de pagamento. Verifique os dados e tente novamente.', VINDI) , true);

    if ($this->gateway->verify_method())
      $this->verify_payment_profile($payment_profile['id']);
  }

  /**
   * @param int $payment_profile_id
   *
   * @throws Exception
   */
  protected function verify_payment_profile($payment_profile_id)
  {
    if (!$this->routes->verifyCustomerPaymentProfile($payment_profile_id))
      $this->abort(__('Não foi possível realizar a verificação do seu cartão de crédito!', VINDI) , true);
  }

  /**
   * @param array $item
   *
   */
  private function return_cycle_from_product_type($item)
  {
    if ($item['type'] == 'shipping' || $item['type'] == 'tax') {
      if ($this->vindi_settings->get_shipping_and_tax_config()) return 1;
    }
    elseif (!$this->is_subscription_type(wc_get_product($item['product_id'])) || $this->is_one_time_shipping(wc_get_product($item['product_id']))) {
      return 1;
    }
    return null;
  }

  /**
   * @param WC_Product $item
   */
  private function is_one_time_shipping($item)
  {
    return reset(get_post_meta($item->id) ['_subscription_one_time_shipping']) == 'yes';
  }

  /**
   * @return array
   * @throws Exception
   */
  protected function build_product_items($order_type = 'bill')
  {
    $call_build_items = "build_product_items_for_{$order_type}";

    if (false === method_exists($this, $call_build_items)) {
      $this->abort(__("Ocorreu um erro ao gerar o seu pedido!", VINDI) , true);
    }

    $product_items = [];
    $order_items = $this->build_product_order_items();
    $order_items[] = $this->build_shipping_item();
    $order_items[] = $this->build_tax_item();

    if ('bill' === $order_type) {
      $order_items[] = $this->build_discount_item_for_bill();
    }

    foreach ($order_items as $order_item) {
      if (empty($order_item)) {
        continue;
      }
      $product_items[] = $this->$call_build_items($order_item);
    }

    if (empty($product_items)) {
      return $this->abort(__('Falha ao recuperar informações sobre o produto na Vindi. Verifique os dados e tente novamente.', VINDI) , true);
    }

    return $product_items;
  }

  protected function build_product_order_items()
  {
    $order_items = $this->order->get_items();

    foreach ($order_items as $key => $order_item) {
      $product = $this->get_product($order_item);
      $order_items[$key]['type'] = 'product';
      $order_items[$key]['vindi_id'] = $product->vindi_id;
      $order_items[$key]['price'] = (float)$order_items[$key]['subtotal'] / $order_items[$key]['qty'];
    }

    return $order_items;
  }

  protected function build_shipping_item()
  {
    $shipping_item = [];
    $shipping_method = $this->order->get_shipping_method();

    if (empty($shipping_method)) return $shipping_item;

    $item = $this->routes->findOrCreateProduct("Frete ($shipping_method)", sanitize_title($shipping_method));
    $shipping_item = array(
      'type' => 'shipping',
      'vindi_id' => $item['id'],
      'price' => (float)$this->order->get_total_shipping() ,
      'qty' => 1,
    );

    return $shipping_item;
  }

  protected function build_tax_item()
  {
    $taxItem = [];
    $taxTotal = $this->vindi_settings->woocommerce->cart->get_total_tax();
    if (empty($taxTotal)) {
      return $taxItem;
    }

    $item = $this->routes->findOrCreateProduct("Taxa", 'wc-tax');
    $taxItem = array(
      'type' => 'tax',
      'vindi_id' => $item['id'],
      'price' => (float)$taxTotal,
      'qty' => 1
    );

    return $taxItem;
  }

  protected function build_discount_item_for_bill()
  {
    $discount_item = [];
    $total_discount = $this->order->get_total_discount();

    if (empty($total_discount)) {
      return $discount_item;
    }

    $item = $this->routes->findOrCreateProduct("Cupom de desconto", 'wc-discount');
    $discount_item = array(
      'type' => 'discount',
      'vindi_id' => $item['id'],
      'price' => (float)$total_discount * -1,
      'qty' => 1
    );

    return $discount_item;
  }

  protected function build_product_items_for_bill($order_item)
  {
    $item = array(
      'product_id' => $order_item['vindi_id'],
      'quantity' => $order_item['qty'],
      'pricing_schema' => array(
        'price' => $order_item['price'],
        'schema_type' => 'per_unit'
      )
    );

    if ('discount' == $order_item['type']) {
      $item = array(
        'product_id' => $order_item['vindi_id'],
        'amount' => $order_item['price']
      );
    }

    return $item;
  }

  protected function build_product_items_for_subscription($order_item)
  {
    $product_item = array(
      'product_id' => $order_item['vindi_id'],
      'quantity' => $order_item['qty'],
      'cycles' => $this->return_cycle_from_product_type($order_item) ,
      'pricing_schema' => array(
        'price' => $order_item['price'],
        'schema_type' => 'per_unit'
      )
    );
    if (!empty($this->order->get_total_discount()) && $order_item['type'] == 'line_item') {
      $product_item['discounts'] = array(
        array(
          'discount_type' => 'percentage',
          'percentage' => ($this->order->get_total_discount() / $this->order->get_subtotal()) * 100,
          'cycles' => $this->config_discount_cycles()
        )
      );
    }
    return $product_item;
  }

  protected function config_discount_cycles()
  {
    $get_plan_length =
    function ($cicle_count)
    {
      if (!$cicle_count) {
        return  null;
      }
      $plan_cycles = (int) WC()->session->get('current_plan')['billing_cycles'];

      if ($plan_cycles) {
        return min($plan_cycles, $cicle_count);
      }
      return $cicle_count;
    };
    $cicle_count = get_post_meta(array_values($this->vindi_settings->woocommerce->cart->get_coupons())[0]->id, 'cicle_count', true);

    switch ($cicle_count) {
      case '0':
        return null;
      default:
        return $get_plan_length($cicle_count);
    }
  }

  /**
   * @return int
   */
  protected function installments()
  {
    if ('credit_card' == $this->payment_method_code() && !is_null($_POST['vindi_cc_installments'])) return $_POST['vindi_cc_installments'];

    return 1;
  }

  /**
   * @param $customer_id
   *
   * @return array
   * @throws Exception
   */
  protected function create_subscription($customer_id)
  {
    $vindi_plan = $this->get_plan();
    $wc_subscription_array = wcs_get_subscriptions_for_order($this->order->id);
    $wc_subscription = end($wc_subscription_array);

    $body = array(
      'customer_id' => $customer_id,
      'payment_method_code' => $this->payment_method_code() ,
      'plan_id' => $vindi_plan,
      'product_items' => $this->build_product_items('subscription') ,
      'code' => $wc_subscription->id,
      'installments' => $this->installments()
    );

    $subscription = $this->routes->createSubscription($body);

    if (!isset($subscription['id']) || empty($subscription['id'])) {
      $this->logger->log(sprintf('Erro no pagamento do pedido %s.', $this->order->id));

      $message = sprintf(__('Pagamento Falhou. (%s)', VINDI) , $this->routes->api->last_error);
      $this->order->update_status('failed', $message);

      throw new Exception($message);
    }
    WC()->session->__unset('current_payment_profile');
    WC()->session->__unset('current_customer');
    return $subscription;
  }

  /**
   * @param int $customer_id
   *
   * @return int
   * @throws Exception
   */
  protected function create_bill($customer_id)
  {
    $body = array(
      'customer_id' => $customer_id,
      'payment_method_code' => $this->payment_method_code() ,
      'bill_items' => $this->build_product_items('bill') ,
      'code' => $this->order->id,
      'installments' => $this->installments()
    );

    $bill = $this->routes->createBill($body);

    if (!$bill) {
      $this->logger->log(sprintf('Erro no pagamento do pedido %s.', $this->order->id));
      $message = sprintf(__('Pagamento Falhou. (%s)', VINDI) , $this->routes->api->last_error);
      $this->order->update_status('failed', $message);

      throw new Exception($message);
    }
    WC()->session->__unset('current_payment_profile');
    WC()->session->__unset('current_customer');
    return $bill;
  }

  protected function add_download_url_meta_for_order($sale, $subscription)
  {
    if ($subscription) {
      if (isset($sale['bill']) && isset($sale['bill']['charges']) && count($sale['bill']['charges'])) {
        add_post_meta($this->order->id, 'vindi_wc_bank_slip_download_url', $sale['bill']['charges'][0]['print_url']);
      }
      return;
    }

    if (isset($sale['charges']) && count($sale['charges'])) {
      add_post_meta($this->order->id, 'vindi_wc_bank_slip_download_url', $sale['charges'][0]['print_url']);
    }
  }

  protected function cancel_if_denied_bill_status($bill)
  {
    if (empty($bill['charges'])) {
      return false;
    }

    $last_charge = end($bill['charges']);
    $transaction_status = $last_charge['last_transaction']['status'];
    $denied_status = ['rejected' => 'Infelizmente não foi possível autorizar seu pagamento.', 'failure' => 'Ocorreu um erro ao aprovar a transação, tente novamente.'];

    if (array_key_exists($transaction_status, $denied_status)) {
      return $denied_status[$transaction_status];
    }

    return false;
  }

  /**
   * @return array
   */
  protected function finish_payment($bill)
  {
    $this->vindi_settings->woocommerce->cart->empty_cart();

    if ($bill['status'] == 'paid') {
      $status = $this->vindi_settings->get_return_status();
      $status_message = __('O Pagamento foi realizado com sucesso pela Vindi.', VINDI);
    }
    else {
      $data_to_log = sprintf('Aguardando pagamento do pedido %s pela Vindi.', $this->order->id);
      $status_message = __('Aguardando pagamento do pedido.', VINDI);
      $status = 'pending';
    }

    $this->logger->log($data_to_log);
    $this->order->update_status($status, $status_message);

    return array(
      'result' => 'success',
      'redirect' => $this->order->get_checkout_order_received_url() ,
    );
  }

  /**
   * @param array $product
   *
   */
  protected function get_product($order_item)
  {
    $product = $this->order->get_product_from_item($order_item);
    $product->vindi_id = (int) $product->get_meta('vindi_product_id');
    //TODO: if product doesn't exist, create
    return $product;
  }

  protected function parse_variation_name($attributes, $order_item)
  {
    $keys = array_keys($attributes);
    $names = [];

    foreach ($order_item['item_meta'] as $key => $meta) {
      if (in_array($key, $keys)) {
        $names[] = $meta;
      }
    }

    return join(' - ', $names);
  }

  protected function is_variable($product)
  {
    return (boolean)preg_match('/variation/', $product->get_type());
  }

  protected function is_subscription_type(WC_Product $product)
  {
    return (boolean)preg_match('/subscription/', $product->get_type());
  }
}
