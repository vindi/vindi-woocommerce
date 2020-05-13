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

  /**
   * @var VindiControllers
   */
  private $controllers;

  function __construct(WC_Order $order, VindiPaymentGateway $gateway, VindiSettings $vindi_settings, VindiControllers $controllers)
  {
    $this->order = $order;
    $this->gateway = $gateway;
    $this->vindi_settings = $vindi_settings;
    $this->logger = $vindi_settings->logger;
    $this->routes = $vindi_settings->routes;
    $this->controllers = $controllers;
  }

  /**
   * Validate order to chose payment type.
   * @return int order type.
   */
  public function get_order_type()
  {
    if (wcs_order_contains_subscription($this->order, array('any'))) {
      return static::ORDER_TYPE_SUBSCRIPTION;
    }

    return static::ORDER_TYPE_SINGLE;
  }


  /**
   * Find or Create a Customer at Vindi for the given credentials.
   * @return array|bool
   */
  public function get_customer()
  {
    $current_user = wp_get_current_user();
    $vindi_customer_id = get_user_meta($current_user->ID, 'vindi_customer_id', true);
    $vindi_customer = $this->routes->findCustomerById($vindi_customer_id);
    if(!$vindi_customer) {
      $vindi_customer = $this->controllers->customers->create($current_user->ID, $this->order);
    }
    if($this->vindi_settings->send_nfe_information()) {
      $vindi_customer = $this->controllers->customers->update($current_user->ID, $this->order);
    }

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
        return $this->process_order();
      case static ::ORDER_TYPE_SUBSCRIPTION:
        return $this->process_order();
      case static ::ORDER_TYPE_INVALID:
      default:
        return $this->abort(__('Falha ao processar carrinho de compras. Verifique os itens escolhidos e tente novamente.', VINDI) , true);
    }
  }

  /**
   * @return array
   * @throws Exception
   */
  public function process_order()
  {
    $customer = $this->get_customer();
    $wc_subscriptions = wcs_get_subscriptions_for_order($this->order);

    $order_items = $this->order->get_items();
    $bills = [];
    $order_post_meta = [];
    $bill_products = [];
    foreach ($order_items as $order_item) {
      $product = $order_item->get_product();

      if($this->is_subscription_type($product)) {
        $subscription = $this->create_subscription($customer['id'], $order_item);
        $subscription_id = $subscription['id'];
        $subscription_wc_id = $subscription['wc_id'];
        $subscription_bill = $subscription['bill'];
        $order_post_meta[$subscription_id]['cycle'] = $subscription['current_period']['cycle'];
        $order_post_meta[$subscription_id]['bill'] = $this->create_bill_meta_for_order($subscription_bill);
        $bills[] = $subscription['bill'];
        
        update_post_meta($subscription_wc_id, 'vindi_subscription_id', $subscription_id);
        continue;
      }
      
      $bill_products[] = $order_item;
    }
    // return false;
    
    if(!empty($bill_products)) {
      $single_payment_bill = $this->create_bill($customer['id'], $bill_products);
      $order_post_meta['single_payment']['bill'] = $this->create_bill_meta_for_order($single_payment_bill);

      $bills[] = $single_payment_bill;
    }

    update_post_meta($this->order->id, 'vindi_order', $order_post_meta);

    WC()->session->__unset('current_payment_profile');
    WC()->session->__unset('current_customer');

    remove_action('woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::prepare_renewal');

    return $this->finish_payment($bills);
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
    $product = method_exists($item, 'get_product') ? $item->get_product() : false;
    if ($item['type'] == 'shipping' || $item['type'] == 'tax') {
      if ($this->vindi_settings->get_shipping_and_tax_config()) return 1;
    }
    elseif (!$this->is_subscription_type($product) || $this->is_one_time_shipping($product)) {
      return 1;
    }
    $cycles = get_post_meta($product->id, '_subscription_length', true);
    return $cycles > 0 ? $cycles : null;
  }

  /**
   * @param WC_Product $item
   */
  private function is_one_time_shipping($product)
  {
    return get_post_meta($product->id, '_subscription_one_time_shipping', true) == 'yes';
  }

  /**
   * Build the array of product(s), shipping, tax and discounts to send to Vindi
   *
   * @param string $order_type Order type. Possible values 'bill' and 'subscription', defaults to 'bill'
   * @param WC_Order_Item_Product|WC_Order_Item_Product[] $product The product to be built. If the order type is 'bill' this will be an array of WC_Order_Item_Product,
   * if it's 'subscription' it will be a single WC_Order_Item_Product.
   *
   * @return array
   *
   * @throws Exception
   */
  protected function build_product_items($order_type = 'bill', $product)
  {
    $call_build_items = "build_product_items_for_{$order_type}";

    if (false === method_exists($this, $call_build_items)) {
      $this->abort(__("Ocorreu um erro ao gerar o seu pedido!", VINDI) , true);
    }

    $product_items = [];
    $order_items = [];
    if('bill' === $order_type) {
      $order_items = $this->build_product_from_order_item($order_type, $product);
    } else {
      $order_items[] = $this->build_product_from_order_item($order_type, $product);
    }
    // TODO Buscar separadamente o valor de entrega, imposto e desconto
    $order_items[] = $this->build_shipping_item($order_items);
    $order_items[] = $this->build_tax_item($order_items);

    if ('bill' === $order_type) {
      $order_items[] = $this->build_discount_item_for_bill($order_items);
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

  protected function build_product_from_order_item($order_type, $order_items)
  {
    if('bill' === $order_type) {
      foreach ($order_items as $key => $order_item) {
        $product = $this->get_product($order_item);
        $order_items[$key]['type'] = 'product';
        $order_items[$key]['vindi_id'] = $product->vindi_id;
        $order_items[$key]['price'] = (float)$order_items[$key]['subtotal'] / $order_items[$key]['qty'];
      }
      return $order_items;
    }
    $product = $this->get_product($order_items);
    $order_items['type'] = 'product';
    $order_items['vindi_id'] = $product->vindi_id;
    $order_items['price'] = (float)$order_items['subtotal'] / $order_items['qty'];

    return $order_items;
  }

  protected function build_shipping_item($order_items)
  {
    $shipping_item = [];
    $shipping_method = $this->order->get_shipping_method();

    // foreach ($order_items as $order_item) {
      
    // }

    if (empty($shipping_method)) return $shipping_item;

    $item = $this->routes->findOrCreateProduct("Frete ($shipping_method)", sanitize_title($shipping_method));
    $shipping_item = array(
      'type' => 'shipping',
      'vindi_id' => $item['id'],
      'price' => (float)$this->order->get_total_shipping(),
      'qty' => 1,
    );

    return $shipping_item;
  }

  protected function build_tax_item($order_items)
  {
    $taxItem = [];
    $total_order_tax = $this->vindi_settings->woocommerce->cart->get_total_tax();
    $total_tax = 0;
    if (empty($total_order_tax)) {
      return $taxItem;
    }

    foreach ($order_items as $order_item) {
      if(!empty($order_item['type'])) {
        if ($order_item['type'] === 'shipping') {
          $total_tax += (float)($this->order->get_shipping_tax());
        } else {
          $total_tax += (float)($order_item->get_total_tax());
        }
      }
    }

    $item = $this->routes->findOrCreateProduct("Taxa", 'wc-tax');
    $taxItem = array(
      'type' => 'tax',
      'vindi_id' => $item['id'],
      'price' => (float)$total_tax,
      'qty' => 1
    );

    return $taxItem;
  }

  protected function build_discount_item_for_bill($order_items)
  {
    $discount_item = [];
    $coupons = array_values($this->vindi_settings->woocommerce->cart->get_coupons());
    $bill_total_discount = 0;
    foreach ($order_items as $order_item) {
      $bill_total_discount += (float) ($order_item['subtotal'] - $order_item['total']);
    }
    
    if (empty($bill_total_discount)) {
      return $discount_item;
    }

    $item = $this->routes->findOrCreateProduct("Cupom de desconto", 'wc-discount');
    $discount_item = array(
      'type' => 'discount',
      'vindi_id' => $item['id'],
      'price' => (float)$bill_total_discount * -1,
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
    $plan_cycles = $this->return_cycle_from_product_type($order_item);
    $product_item = array(
      'product_id' => $order_item['vindi_id'],
      'quantity' => $order_item['qty'],
      'cycles' => $plan_cycles,
      'pricing_schema' => array(
        'price' => $order_item['price'],
        'schema_type' => 'per_unit'
      )
    );

    if (!empty($this->order->get_total_discount()) && $order_item['type'] == 'line_item') {
      $product_item['discounts'] = [];

      $coupons = array_values($this->vindi_settings->woocommerce->cart->get_coupons());
      foreach ($coupons as $coupon) {
        if($this->coupon_supports_product($order_item, $coupon)) {
          $product_item['discounts'][] = $this->build_discount_item_for_subscription($coupon, $plan_cycles);
        }
      }
    }
    return $product_item;
  }

  protected function coupon_supports_product($order_item, $coupon)
  {
    $product_id = $order_item->get_product()->id;
    $included_products = $coupon->get_product_ids();
    $excluded_products = $coupon->get_excluded_product_ids();

    if(!empty($excluded_products)) {
      if(in_array($product_id, $excluded_products)) {
        // The coupon doesn't support the current product
        return false;
      }
    }
    if(!empty($included_products)) {
      if(!in_array($product_id, $included_products)) {
        // The coupon doesn't support the current product
        return false;
      }
    }

    return true;
  }

  protected function build_discount_item_for_subscription($coupon, $plan_cycles = 0) {
    $discount_item = [];
    
    $amount = $coupon->get_amount();
    $discount_type = $coupon->get_discount_type();
    if($discount_type == 'fixed_cart') {
      $discount_item['discount_type'] = 'amount';
      $discount_item['amount'] = $amount / $this->order->get_item_count();
      $discount_item['cycles'] = 1;
      return $discount_item;
    } elseif(strpos($discount_type, 'fixed') !== false) {
      $discount_item['discount_type'] = 'amount';
      $discount_item['amount'] = $amount;
    } elseif(strpos($discount_type, 'percent') !== false) {
      $discount_item['discount_type'] = 'percentage';
      $discount_item['percentage'] = $amount;
    }
    $discount_item['cycles'] = $this->config_discount_cycles($coupon, $plan_cycles);

    return $discount_item;
  }

  protected function config_discount_cycles($coupon, $plan_cycles = 0)
  {
    $get_plan_length =
    function ($cycle_count, $plan_cycles)
    {
      if (!$cycle_count) {
        return null;
      }

      if ($plan_cycles) {
        return min($plan_cycles, $cycle_count);
      }
      return $cycle_count;
    };
    $cycle_count = get_post_meta($coupon->id, 'cycle_count', true);

    switch ($cycle_count) {
      case '0':
        return null;
      default:
        return $get_plan_length($cycle_count, $plan_cycles);
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
   * Retrieve Plan for Vindi Subscription.
   * @return int|bool
   */
  public function get_plan_from_order_item($order_item)
  {
    $product = $order_item->get_product();

    if (isset($order_item['variation_id']) && $order_item['variation_id'] != 0) {
      $vindi_plan = get_post_meta($order_item['variation_id'], 'vindi_plan_id', true);
      if (empty($vindi_plan) || !is_numeric($vindi_plan) || is_null($vindi_plan) || $vindi_plan == 0) {
        $vindi_plan = get_post_meta($product->id, 'vindi_plan_id', true);
      }
    }
    else $vindi_plan = get_post_meta($product->id, 'vindi_plan_id', true);

    if ($this->is_subscription_type($product) and !empty($vindi_plan)) return $vindi_plan;

    $this->abort(__('O produto selecionado não é uma assinatura.', VINDI) , true);
  }


  /**
   * @param $customer_id
   * @param WC_Order_item_product $order_item
   *
   * @return array
   * @throws Exception
   */
  protected function create_subscription($customer_id, $order_item)
  {
    $vindi_plan = $this->get_plan_from_order_item($order_item);
    $wc_subscription_id = VindiHelpers::get_matching_subscription($this->order, $order_item)->id;
    $data = array(
      'customer_id' => $customer_id,
      'payment_method_code' => $this->payment_method_code(),
      'plan_id' => $vindi_plan,
      'product_items' => $this->build_product_items('subscription', $order_item),
      'code' => $wc_subscription_id,
      'installments' => $this->installments()
    );

    $subscription = $this->routes->createSubscription($data);

    // TODO caso ocorra o erro no pagamento de uma assinatura cancelar as outras
    if (!isset($subscription['id']) || empty($subscription['id'])) {
      $this->logger->log(sprintf('Erro no pagamento item %s do pedido %s.', $order_item->name, $this->order->id));

      $message = sprintf(__('Pagamento Falhou. (%s)', VINDI) , $this->vindi_settings->api->last_error);
      $this->order->update_status('failed', $message);

      throw new Exception($message);
    }

    $subscription['wc_id'] = $wc_subscription_id;

    return $subscription;
  }

  /**
   * @param int $customer_id
   *
   * @return int
   * @throws Exception
   */
   protected function create_bill($customer_id, $order_items)
   {
     $data = array(
       'customer_id' => $customer_id,
       'payment_method_code' => $this->payment_method_code() ,
       'bill_items' => $this->build_product_items('bill', $order_items) ,
       'code' => $this->order->id,
       'installments' => $this->installments()
     );
 
     $bill = $this->routes->createBill($data);
 
     if (!$bill) {
       $this->logger->log(sprintf('Erro no pagamento do pedido %s.', $this->order->id));
       $message = sprintf(__('Pagamento Falhou. (%s)', VINDI) , $this->vindi_settings->api->last_error);
       $this->order->update_status('failed', $message);
 
       throw new Exception($message);
     }
     return $bill;
   }

  protected function create_bill_meta_for_order($bill)
  {
    $bill_meta['id'] = $bill['id'];
    $bill_meta['status'] = $bill['status'];
    if (isset($bill['charges']) && count($bill['charges'])) {
      $bill_meta['bank_slip_url'] = $bill['charges'][0]['print_url'];
    }
    return $bill_meta;
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
  protected function finish_payment($bills)
  {
    $this->vindi_settings->woocommerce->cart->empty_cart();

    $last_status = 'paid';
    foreach ($bills as $bill) {
      if ($bill['status'] == 'paid') {
        $data_to_log = sprintf('O Pagamento da fatura %s do pedido %s foi realizado com sucesso pela Vindi.', $bill['id'], $this->order->id);
        $status_message = __('O Pagamento foi realizado com sucesso pela Vindi.', VINDI);
      } else {
        $data_to_log = sprintf('Aguardando pagamento da fatura %s do pedido %s pela Vindi.', $bill['id'], $this->order->id);
        $status_message = __('Aguardando pagamento do pedido.', VINDI);
      }
      if($last_status != 'paid') {
        $status = 'pending';
      } else {
        $status = $this->vindi_settings->get_return_status();
      }
      $this->logger->log($data_to_log);
      $last_status = $bill['status'];
    }
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
    $product = $order_item->get_product();
    $product_id = $product->id;
    $vindi_product_id = get_post_meta($product_id, 'vindi_product_id', true);
    
    if (!$vindi_product_id) {
      $vindi_product = null;
      if(!$this->is_subscription_type($product)) {
        $vindi_product = $this->controllers->products->create($product_id, '', '', true);
      } else {
        $vindi_product = $this->controllers->plans->create($product_id, '', '', true);
      }

      $vindi_product_id = $vindi_product['id'];
    }

    $product->vindi_id = (int) $vindi_product_id;
    return $product;
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
