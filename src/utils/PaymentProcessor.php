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

  /**
   * @var bool
   */
  private $shipping_added;

  /**
   * Payment Processor contructor.
   *
   * @param WC_Order $order The order to be processed
   * @param VindiPaymentGateway $gateway Current payment gateway
   * @param VindiSettings $vindi_settings The VindiSettings instance
   * @param VindiControllers $controllers VindiControllers instance
   */
  function __construct(WC_Order $order, VindiPaymentGateway $gateway, VindiSettings $vindi_settings, VindiControllers $controllers)
  {
    $this->order = $order;
    $this->gateway = $gateway;
    $this->vindi_settings = $vindi_settings;
    $this->logger = $vindi_settings->logger;
    $this->routes = $vindi_settings->routes;
    $this->controllers = $controllers;
    $this->shipping_added = false;
  }

  /**
   * Check if the order contains any subscription.
   *
   * @return int Order type
   */
  public function get_order_type()
  {
    if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($this->order, array('any'))) {
      return static::ORDER_TYPE_SUBSCRIPTION;
    }

    return static::ORDER_TYPE_SINGLE;
  }


  /**
   * Find or create a customer within Vindi using the given credentials.
   *
   * @return array Vindi customer array
   */
  public function get_customer()
  {
    $current_user = wp_get_current_user();
	
    $vindi_customer_id = get_user_meta($current_user->ID, 'vindi_customer_id', true);
	$this->logger->log(sprintf('Vindi Customer ID : %s', $vindi_customer_id));
    //  $vindi_customer = $this->routes->findCustomerById($vindi_customer_id);
	 $vindi_customer = false;
	if(isset($vindi_customer_id) && !empty($vindi_customer_id)) {  
	  $vindi_customer = $this->routes->findCustomerById($vindi_customer_id);
	}
    if(!$vindi_customer) {
      $this->logger->log(sprintf('NÃO ACHOU O CUSTOMER - VAI CRIAR AGORA'));
      if($current_user->ID){
        $this->logger->log(sprintf('O id do Usuário novo é: %s',$current_user->ID));
        $vindi_customer = $this->controllers->customers->create($current_user->ID, $this->order);
      }else{
        $this->logger->log(sprintf('Não há id de usuário. Usa o do Admin',1));
        $vindi_customer = $this->controllers->customers->create(1, $this->order);
      }
      
    }
	$this->logger->log(sprintf('Vindi Customer : %s', json_encode($vindi_customer,1)));

    // if($this->vindi_settings->send_nfe_information()) {
      $vindi_customer = $this->controllers->customers->update($current_user->ID, $this->order);
	  $this->logger->log(sprintf('Vindi Customer Atualizado: %s', json_encode($vindi_customer,1)));
    // }
	

    if ($this->is_cc())
      $this->create_payment_profile($vindi_customer['id']);

    $this->logger->log(sprintf('Cliente Vindi --: %s', json_encode($vindi_customer),1));

    return $vindi_customer;
  }

  /**
   * Build the credit card payment type.
   *
   * @param int $customer_id Vindi customer id
   * 
   * @return array
   */
  public function get_cc_payment_type($customer_id)
  {
    if ($this->gateway->verify_user_payment_profile()) return false;

    return array(
      'customer_id' => $customer_id,
      'holder_name' => sanitize_text_field($_POST['vindi_cc_fullname']),
      'card_expiration' => filter_var($_POST['vindi_cc_monthexpiry'], FILTER_SANITIZE_NUMBER_INT) . '/' . filter_var($_POST['vindi_cc_yearexpiry'], FILTER_SANITIZE_NUMBER_INT),
      'card_number' => filter_var($_POST['vindi_cc_number'], FILTER_SANITIZE_NUMBER_INT),
      'card_cvv' => filter_var($_POST['vindi_cc_cvc'], FILTER_SANITIZE_NUMBER_INT),
      'payment_method_code' => $this->payment_method_code() ,
      'payment_company_code' => sanitize_text_field($_POST['vindi_cc_paymentcompany']),
    );
  }

  /**
   * Check if payment method is "Credit Card"
   *
   * @return bool
   */
  public function is_cc()
  {
    return 'cc' === $this->gateway->type();
  }

  /**
   * Check if payment method is "Bank Slip"
   *
   * @return bool
   */
  public function is_bank_slip()
  {
    return 'bank_slip' === $this->gateway->type();
  }

  /**
   * Retrieve payment method code
   *
   * @return string Vindi payment method code
   */
  public function payment_method_code()
  {
    return $this->is_cc() ? 'credit_card' : 'bank_slip';
  }

  /**
   * Interrupt the payment process and throw an error if needed.
   * Log the message, add it to the order note and send an alert to the user
   *
   * @param string $message The error message
   * @param bool $throw_exception When true an exception is thrown
   *
   * @return bool Always returns false
   *
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
   * Check if the order type is valid and process it.
   *
   * @return array|void
   *
   * @throws Exception
   */
  public function process()
  {
    switch ($orderType = $this->get_order_type()) {
      case static ::ORDER_TYPE_SINGLE:
      case static ::ORDER_TYPE_SUBSCRIPTION:
        return $this->process_order();
      case static ::ORDER_TYPE_INVALID:
      default:
        return $this->abort(__('Falha ao processar carrinho de compras. Verifique os itens escolhidos e tente novamente.', VINDI) , true);
    }
  }

  /**
   * Process current order.
   *
   * @return array
   *
   * @throws Exception
   */
  public function process_order()
  {
    if($this->order_has_trial_and_simple_product())  {
      $message = __('Não é possível comprar produtos simples e assinaturas com trial no mesmo pedido!', VINDI);
      $this->order->update_status('failed', $message);
      wc_add_notice($message, 'error');

      throw new Exception($message);
      return false;
    }

    $customer = $this->get_customer();
    $order_items = $this->order->get_items();
	$this->logger->log(sprintf('ORDER ITEMS: %s',json_encode($order_items,1)));
    $bills = [];
    $order_post_meta = [];
    $bill_products = [];
    $subscriptions_ids = [];
    foreach ($order_items as $order_item) {
      $product = $order_item->get_product();
	  $this->logger->log(sprintf('PRODUCT FOUND: %s',json_encode($product,1)));
      if($this->is_subscription_type($product)) {
        $subscription = $this->create_subscription($customer['id'], $order_item);
        $subscription_order_post_meta = [];
        $subscription_id = $subscription['id'];
        array_push($subscriptions_ids, $subscription_id);
        $wc_subscription_id = $subscription['wc_id'];
        $subscription_bill = $subscription['bill'];
        $order_post_meta[$subscription_id]['cycle'] = $subscription['current_period']['cycle'];
        $order_post_meta[$subscription_id]['product'] = $product->name;
        $order_post_meta[$subscription_id]['bill'] = $this->create_bill_meta_for_order($subscription_bill);

        $subscription_order_post_meta[$subscription_id]['cycle'] = $subscription['current_period']['cycle'];
        $subscription_order_post_meta[$subscription_id]['product'] = $product->name;
        $subscription_order_post_meta[$subscription_id]['bill'] = $this->create_bill_meta_for_order($subscription_bill);
        $bills[] = $subscription['bill'];
        if ($message = $this->cancel_if_denied_bill_status($subscription['bill'])) {
          $wc_subscription = wcs_get_subscription($wc_subscription_id);
          $wc_subscription->update_status('cancelled', __($message, VINDI));
          $this->order->update_status('cancelled', __($message, VINDI));
          $this->suspend_subscriptions($subscriptions_ids);
          $this->cancel_bills($bills, __('Algum pagamento do pedido não pode ser processado', VINDI));
          $this->abort(__($message, VINDI) , true);
        }

        update_post_meta($wc_subscription_id, 'vindi_subscription_id', $subscription_id);
        update_post_meta($wc_subscription_id, 'vindi_order', $subscription_order_post_meta);
        continue;
      }
		$this->logger->log(sprintf('Cadastrando os produtos ORder: %s',$order_item));
      	array_push($order_item,$bill_products);
    }

    if(!empty($bill_products)) {
		$this->logger->log('*B*I*L*L*S*');
	   $this->logger->log(sprintf('Cadastrando os produtos na Conta: %s',$order_item));
      $single_payment_bill = $this->create_bill($customer['id'], $bill_products);
		
		
		$this->logger->log(sprintf('Bills Section: %s', json_encode($single_payment_bill)));
      $order_post_meta['single_payment']['product'] = 'Produtos Avulsos';
      $order_post_meta['single_payment']['bill'] = $this->create_bill_meta_for_order($single_payment_bill);
		
      $bills[] = $single_payment_bill;
		
		$this->logger->log(sprintf('Bills json: %s',json_encode($bills)));
		$this->logger->log(sprintf('Bills: %s',$bills));
		$this->logger->log(sprintf('Bills: %s',json_encode($bills)));
		
      if ($message = $this->cancel_if_denied_bill_status($single_payment_bill)) {
        $this->order->update_status('cancelled', __($message, VINDI));
        $this->suspend_subscriptions($subscriptions_ids);
        $this->cancel_bills($bills, __('Algum pagamento do pedido não pode ser processado', VINDI));
        $this->abort(__($message, VINDI) , true);
      }
    }

    update_post_meta($this->order->id, 'vindi_order', $order_post_meta);

    WC()->session->__unset('current_payment_profile');
    WC()->session->__unset('current_customer');

    remove_action('woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::prepare_renewal');

    return $this->finish_payment($bills);
  }



  /**
   * Create a payment profile for the customer
   *
   * @param int $customer_id Vindi customer id
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
   * Check if the payment profile is valid
   *
   * @param int $payment_profile_id The customer's payment profile id
   *
   * @throws Exception
   */
  protected function verify_payment_profile($payment_profile_id)
  {
    if (!$this->routes->verifyCustomerPaymentProfile($payment_profile_id))
      $this->abort(__('Não foi possível realizar a verificação do seu cartão de crédito!', VINDI) , true);
  }

  /**
   * Get the subscription/product expiration time in months
   *
   * @param WC_Order_Item_Product $item
   *
   * @return int
   */
  private function get_cycle_from_product_type($item)
  {
    $product = method_exists($item, 'get_product') ? $item->get_product() : false;
    $this->logger->log(sprintf("Cycles get_product: %s", $product));
    if ($item['type'] == 'shipping' || $item['type'] == 'tax') {
      if ($this->vindi_settings->get_shipping_and_tax_config()) return 1;
    }
    elseif (!$this->is_subscription_type($product) || $this->is_one_time_shipping($product)) {
      return 1;
    }
    $cycles = 1;
    if($product){
        $cycles = get_post_meta($product->get_id(), '_subscription_length', true);
    }
    return $cycles > 0 ? $cycles : 0;
  }

  /**
   * Check if the product needs to be shipped only once
   *
   * @param WC_Product $product Woocommerce product
   *
   * @return bool
   */
  private function is_one_time_shipping($product)
  {
    return get_post_meta($product->get_id(), '_subscription_one_time_shipping', true) == 'yes';
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
  public function build_product_items($order_type = 'bill', $product)
  {
    $call_build_items = "build_product_items_for_{$order_type}";

    if (false === method_exists($this, $call_build_items)) {
      $this->abort(__("Ocorreu um erro ao gerar o seu pedido!", VINDI) , true);
    }

    $product_items = [];
    $order_items = [];
	  $this->logger->log(sprintf('Gerando os itens do Produto: %s',json_encode($product)));
    if('bill' === $order_type) {
      $order_items = $this->build_product_from_order_item($order_type, $product);
    } else {
      $order_items[] = $this->build_product_from_order_item($order_type, $product);
    }
    // TODO Buscar separadamente o valor de entrega
    $order_items[] = $this->build_shipping_item($order_items);
    $order_items[] = $this->build_tax_item($order_items);

    if ('bill' === $order_type) {
      $order_items[] = $this->build_discount_item_for_bill($order_items);
      $order_items[] = $this->build_interest_rate_item($order_items);
    }

    foreach ($order_items as $order_item) {
      if (empty($order_item)) {
        continue;
      }
	$this->logger->log(sprintf("Build Item %s",json_encode($order_items)));
      $product_items[] = $this->$call_build_items($order_item);
    }

    if (empty($product_items)) {
      return $this->abort(__('Falha ao recuperar informações sobre o produto na Vindi. Verifique os dados e tente novamente.', VINDI) , true);
    }

    return $product_items;
  }

  /**
   * Retrives the product(s) information. Adds the vindi_id, the type and the price to it.
   *
   * @param string $order_type ('subscription' or 'bill')
   * @param WC_Order_Item_Product|WC_Order_Item_Product[] $order_items. Subscriptions will pass only one order_item and
   * Bills will pass an array with all the products to be processed
   *
   * @return array
   */
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
    if ($this->subscription_has_trial($product)) {
      $matching_item = $this->get_trial_matching_subscription_item($order_items);
      $order_items['price'] = (float)$matching_item['subtotal'] / $matching_item['qty'];
    } else {
      $order_items['price'] = (float)$order_items['subtotal'] / $order_items['qty'];
    }

    return $order_items;
  }

  /**
   * Create the shipping item to be added to the bill.
   *
   * @param WC_Order_Item_Product[] $order_items. Array with all items to add
   * the respective delivered value // TODO.
   *
   * @return array
   */
  protected function build_interest_rate_item($order_items)
  {
    $interest_rate_item = [];

    if (!($this->is_cc() && $this->installments() > 1 && $this->gateway->is_interest_rate_enabled())) {
      return $interest_rate_item;
    }

    $interest_rate = $this->gateway->get_interest_rate();
    $installments = $this->installments();
    $cart = WC()->cart;
    $cart_total = $cart->total;
    foreach ($cart->get_fees() as $index => $fee) {
      if($fee->name == __('Juros', VINDI)) {
        $cart_total -= $fee->amount;
      }
    }
    $total_price = $cart_total * (1 + (($interest_rate / 100) * ($installments - 1)));
    $interest_price = (float) $total_price - $cart_total;

    $item = $this->routes->findOrCreateProduct("Juros", 'wc-interest-rate');
    $interest_rate_item = array(
      'type' => 'interest_rate',
      'vindi_id' => $item['id'],
      'price' => $interest_price,
      'qty' => 1,
    );
    return $interest_rate_item;
  }

  /**
   * Create the shipping item to be added to the bill.
   *
   * @param WC_Order_Item_Product[] $order_items. Array with all items to add
   * the respective delivered value // TODO.
   *
   * @return array
   */
  protected function build_shipping_item($order_items)
  {
    $shipping_item = [];
    $shipping_method = $this->order->get_shipping_method();

    if (empty($shipping_method)) return $shipping_item;

    foreach ($order_items as $order_item) {
      $product = $order_item->get_product();

      if($product->needs_shipping() && !$this->shipping_added) {
        $item = $this->routes->findOrCreateProduct("Frete ($shipping_method)", sanitize_title($shipping_method));
        $shipping_item = array(
          'type' => 'shipping',
          'vindi_id' => $item['id'],
          'price' => (float)$this->order->get_total_shipping(),
          'qty' => 1,
        );
        $this->shipping_added = true;

        return $shipping_item;
      }
    }
  }

  /**
   * Create the tax item.
   *
   * @param WC_Order_Item_Product[] $order_items. Products to calculate the tax amount
   *
   * @return array
   */
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

  /**
   * Create discount item for a bill.
   *
   * @param WC_Order_Item_Product[] $order_items. All the products to calculate
   * the discount amount
   *
   * @return array
   */
  protected function build_discount_item_for_bill($order_items)
  {
    $discount_item = [];
    $coupons = array_values($this->vindi_settings->woocommerce->cart->get_coupons());
    $bill_total_discount = 0;
    foreach ($order_items as $order_item) {
      if(isset($order_item['subtotal']) && isset($order_item['total'])) {
        $bill_total_discount += (float) ($order_item['subtotal'] - $order_item['total']);
      }
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

  /**
   * Create bill product item to send to the Vindi API.
   *
   * @param WC_Order_Item_Product $order_item. The product to be converted to
   * the correct product format.
   *
   * @return array
   */
  protected function build_product_items_for_bill($order_item)
  {
	$this->logger->log(sprintf("BUILD PRODUCT ITEM: %s",json_encode($order_item)));
    $item = array(
      'product_id' => $order_item['vindi_id'],
      'quantity' => $order_item['qty'],
      'pricing_schema' => array(
        'price' => $order_item['price'],
        'schema_type' => 'per_unit'
      )
    );

    if (
      'discount' == $order_item['type'] || 'shipping' == $order_item['type'] ||
      'tax' == $order_item['type'] || 'interest_rate' == $order_item['type']
    ) {
      $item = array(
        'product_id' => $order_item['vindi_id'],
        'amount' => $order_item['price']
      );
    }

    return $item;
  }

  /**
   * Create subscription product item to send to the Vindi API.
   *
   * @param WC_Order_Item_Product $order_item. The product to be converted to
   * the correct product format.
   *
   * @return array
   */
  protected function build_product_items_for_subscription($order_item)
  {
	
    $plan_cycles = $this->get_cycle_from_product_type($order_item);
	$this->logger->log(sprintf("PLAN CYCLES: %s",$plan_cycles));
    $product_item = array(
      'product_id' => $order_item['vindi_id'],
      'quantity' => $order_item['qty'],
      'cycles' => $plan_cycles,
      'pricing_schema' => array(
        'price' => $order_item['price'],
        'schema_type' => 'per_unit'
      )
    );
	$this->logger->log(sprintf("Build Product Item Subs: %s",json_encode($product_item)));
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

  /**
   * Verify that the coupon can be applied to the current product.
   *
   * @param WC_Order_Item_Product $order_item. The product.
   * @param WC_Coupon $coupon. The coupon.
   *
   * @return bool
   */
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

  /**
   * Create a discount item for a subscription.
   *
   * @param WC_Coupon $coupon. The coupon to be added.
   * @param int $plan_cycles. The amount of cycles that the subscription has.
   *
   * @return array
   */
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

  /**
   * Configure the discount cycles that the coupon will be used.
   *
   * @param WC_Coupon $coupon. The coupon to be added.
   * @param int $plan_cycles. The amount of cycles that the subscription has.
   *
   * @return int
   */
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
   * Retrieve number of installments from order.
   * If the order contains subscriptions the return will be 1,
   * else it will be the amount selected by the user during checkout.
   *
   * @return int
   */
  protected function installments()
  {
    if (
      'credit_card' == $this->payment_method_code() &&
      isset($_POST['vindi_cc_installments'])
    ) {
      return filter_var($_POST['vindi_cc_installments'], FILTER_SANITIZE_NUMBER_INT);
    }

    return 1;
  }

  /**
   * Retrieve Plan for Vindi Subscription.
   *
   * @param WC_Order_Item_Product $order_item
   *
   * @return int|bool
   */
  public function get_plan_from_order_item($order_item)
  {
    $product = $order_item->get_product();

    if (isset($order_item['variation_id']) && $order_item['variation_id'] != 0) {
      $vindi_plan = get_post_meta($order_item['variation_id'], 'vindi_plan_id', true);
      if (empty($vindi_plan) || !is_numeric($vindi_plan) || is_null($vindi_plan) || $vindi_plan == 0) {
        $vindi_plan = get_post_meta($product->get_id(), 'vindi_plan_id', true);
      }
    }
    else $vindi_plan = get_post_meta($product->get_id(), 'vindi_plan_id', true);

    if ($this->is_subscription_type($product) and !empty($vindi_plan)) return $vindi_plan;

    $this->abort(__('O produto selecionado não é uma assinatura.', VINDI) , true);
  }


  /**
   * Create a subscription within Vindi
   *
   * @param int $customer_id ID of the customer that placed the order
   * @param WC_Order_item_product $order_item Item to add to the subscription.
   *
   * @return array
   * @throws Exception
   */
  protected function create_subscription($customer_id, $order_item)
  {
	  $this->logger->log('Criando Assinatura');
    $vindi_plan = $this->get_plan_from_order_item($order_item);
	$this->logger->log(sprintf('Vindi Plan: %s',json_encode($vindi_plan)));
	  $this->logger->log(sprintf('==Está ORDER==: %s',$this->order));
	  $this->logger->log(sprintf('==Este ITEM==: %s',$order_item));
    $wc_subscription_id = VindiHelpers::get_matching_subscription($this->order, $order_item)->id;
	  $this->logger->log(sprintf('==SUBSCRIPTION ID==: %s',$wc_subscription_id));
    $data = array(
      'customer_id' => $customer_id,
      'payment_method_code' => $this->payment_method_code(),
      'plan_id' => $vindi_plan,
      'product_items' => $this->build_product_items('subscription', $order_item)[0],
      'code' => $wc_subscription_id,
      'installments' => $this->installments()
    );
	$this->logger->log(sprintf("É aqui que a coisa para: %s",json_encode($data)));
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
   * Create a bill within Vindi
   *
   * @param int $customer_id ID of the customer that placed the order
   * @param WC_Order_item_product[] $order_items Array with items to add to the bill.
   *
   * @return int
   * @throws Exception
   */
  protected function create_bill($customer_id, $order_items)
  {
	$this->logger->log(sprintf('Pagamento Order %s',json_encode($order_items)));  
	$this->logger->log(sprintf('Bills %s',json_encode($this->build_product_items('bill', $order_items))));  
	  
    $data = array(
      'customer_id' => $customer_id,
      'payment_method_code' => $this->payment_method_code() ,
        'bill_items' => $this->build_product_items('bill', $order_items),
		'bill_items' => $order_items,
      'code' => $this->order->id,
      'installments' => $this->installments()
    );
	$this->logger->log(sprintf('Pagamento Order: %s',$order_items));  
	$this->logger->log(sprintf('Criando Pagamento: %s',json_encode($data,1)));
    $bill = $this->routes->createBill($data);

    if (!$bill) {
      $this->logger->log(sprintf('Erro no pagamento do pedido %s.', $this->order->id));
      $message = sprintf(__('Pagamento Falhou. (%s)', VINDI) , $this->vindi_settings->api->last_error);
      $this->order->update_status('failed', $message);

      throw new Exception($message);
    }
    return $bill;
  }

  /**
   * Create bill meta array to add to the order
   *
   * @param array $bill The bill returned from Vindi API
   *
   * @return array
   */
  protected function create_bill_meta_for_order($bill)
  {
    $bill_meta['id'] = $bill['id'];
    $bill_meta['status'] = $bill['status'];
    if (isset($bill['charges']) && count($bill['charges'])) {
      $bill_meta['bank_slip_url'] = $bill['charges'][0]['print_url'];
    }
    return $bill_meta;
  }

  /**
   * Check if bill was rejected by Vindi
   *
   * @param array $bill The bill returned from Vindi API
   *
   * @return bool|string
   */
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
   * Suspend subscription within Vindi
   *
   * @param array $subscriptions_ids Array with the IDs of subscriptions that were processed
   */
  protected function suspend_subscriptions($subscriptions_ids)
  {
    foreach ($subscriptions_ids as $subscription_id) {
      $this->routes->suspendSubscription($subscription_id, true);
    }
  }

  /**
   * Suspend bills within Vindi
   *
   * @param array $bills Array with the bills that were processed
   */
  protected function cancel_bills($bills, $comments = '')
  {
    foreach ($bills as $bill) {
      $this->routes->deleteBill($bill['id'], $comments);
    }
  }

  /**
   * Finish the payment
   *
   * @param array $bills Order bills returned from Vindi API
   *
   * @return array
   */
  protected function finish_payment($bills)
  {
    $this->vindi_settings->woocommerce->cart->empty_cart();

    $bills_status = [];
    foreach ($bills as $bill) {
      if ($bill['status'] == 'paid') {
        $data_to_log = sprintf('O Pagamento da fatura %s do pedido %s foi realizado com sucesso pela Vindi.', $bill['id'], $this->order->id);
        $status_message = __('O Pagamento foi realizado com sucesso pela Vindi.', VINDI);
      } else {
        $data_to_log = sprintf('Aguardando pagamento da fatura %s do pedido %s pela Vindi.', $bill['id'], $this->order->id);
        $status_message = __('Aguardando pagamento do pedido.', VINDI);
      }
      array_push($bills_status, $bill['status']);
      $this->logger->log($data_to_log);
    }
    if(sizeof($bills_status) == sizeof(array_keys($bills_status, 'paid'))) {
      $status = $this->vindi_settings->get_return_status();
    } else {
      $status = 'pending';
    }
    $this->order->update_status($status, $status_message);

    return array(
      'result' => 'success',
      'redirect' => $this->order->get_checkout_order_received_url() ,
    );
  }

  /**
   * Find or create the product within Vindi
   * and add the vindi id to the product array
   *
   * @param WC_Order_Item_Product $order_item
   *
   * @return WC_Product Woocommerce product array with a vindi id
   */
  protected function get_product($order_item)
  {
	$this->logger->log(sprintf('P-R-O-D-U-T-O-S'));
      // $product = $order_item->get_product();
	  $product = $order_item->get_product();
	$this->logger->log(sprintf('PRODUTO: %s', $product));
    $product_id = $order_item->get_id(); 
	$this->logger->log(sprintf('PRODUTO ID: %s', $product_id));
    $vindi_product_id = get_post_meta($product, 'vindi_product_id', true);
	$this->logger->log(sprintf('VINDI PRODUCT ID: %s', $vindi_product_id));

    if (!$vindi_product_id) {
      $vindi_product = null;
      if(!$this->is_subscription_type($product)) {
        $vindi_product = $this->controllers->products->create($product_id, '', '', true);
      } else {
        $vindi_product = $this->controllers->plans->create($product_id, '', '', true);
      }
		$this->logger->log(sprintf("Vindi Product %s",$vindi_product));
      $vindi_product_id = $vindi_product['id'];
    }
	  if(empty($vindi_product_id) || !$vindi_product_id) {
			$this->logger->log("Estava vazio o Vindi");
		  $vindi_product_id = 63;
	  }

    $product->vindi_id = (int) $vindi_product_id;
    return $product;
  }

  /**
   * Check if the order has a subscription with trial and simple products.
   *
   * @since 1.0.0
   * @return bool
   */
  public function order_has_trial_and_simple_product()
  {
    $has_trial = false;
    $has_simple_product = false;
    $order_items = $this->order->get_items();
    foreach ($order_items as $order_item) {
      $product = $order_item->get_product();
      if ($this->subscription_has_trial($product)) {
        $has_trial = true;
        if ($has_simple_product) return true;
      } else {
        $has_simple_product = true;
        if ($has_trial) return true;
      }
    }
    return $has_trial && $has_simple_product;
  }

  /**
   * Check if the product is variable
   *
   * @param WC_Product $product
   * @return bool
   */
  protected function is_variable(WC_Product $product)
  {
    return (boolean)preg_match('/variation/', $product->get_type());
  }

  /**
   * Check if the product is a subscription
   *
   * @param WC_Product $product
   * @return bool
   */
  protected function is_subscription_type(WC_Product $product)
  {
    return (boolean)preg_match('/subscription/', $product->get_type());
  }

  /**
   * Check if the subscription has a trial period
   *
   * @param WC_Product $product
   * @return bool
   */
  protected function subscription_has_trial(WC_Product $product)
  {
    return $this->is_subscription_type($product) && class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::get_trial_length($product->get_id()) > 0;
  }

  /**
   * Get trial item quantity, subtotal and total price.
   *
   * @param WC_Order_Item_Product $order_item
   * @return WC_Order_Item_Product
   */
  protected function get_trial_matching_subscription_item(WC_Order_Item_Product $order_item)
  {
    $subscription = VindiHelpers::get_matching_subscription($this->order, $order_item);
    $matching_item = VindiHelpers::get_matching_subscription_item($subscription, $order_item);
    return $matching_item;
  }
}
