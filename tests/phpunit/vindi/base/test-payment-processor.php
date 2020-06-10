<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/controllers/index.php';
include_once VINDI_PATH . 'src/utils/PaymentProcessor.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Payment_Processor extends Vindi_Test_Base
{
  public function test_build_items()
  {
    $settings = new VindiSettings();
    $controllers = new VindiControllers($settings);

    $credit_card = new VindiCreditGateway($settings, $controllers);
    
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $store_postcode = '82540014';
    update_option('woocommerce_store_postcode', $store_postcode);

    // Arrange: Create a couple of products to use.
    $simple_product = WC_Helper_Product::create_simple_product();

    $simple_product->set_regular_price(20);
    $sample_vindi_id = 1234567;
    $simple_product->save();
    update_post_meta($simple_pruduct->id, 'vindi_product_id', $sample_vindi_id);

    $helper_customer = WC_Helper_Customer::create_customer();

    // Arrange: Set up an order
    $order = WC_Helper_Order::create_order($helper_customer->get_id(), $simple_product);

    $order->set_payment_method($credit_card->id);
    $order->set_payment_method_title($credit_card->method_title);
    $order->save();
    $order->calculate_totals();


    $payment_processor = new VindiPaymentProcessor($order, $credit_card, $settings, $controllers);
    $bill_items = $payment_processor->build_product_items('bill', $order->get_items());
    print_r($bill_items);
  }
}; ?>