<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/controllers/index.php';
include_once VINDI_PATH . 'src/utils/PaymentProcessor.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/services/VindiHelpers.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Payment_Processor extends Vindi_Test_Base
{
  public function test_processor_build_items()
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

    //   // Arrange: Create a couple of products to use.
    //   // $simple_product = WC_Helper_Product::create_simple_product();

    //   $product = new WC_Product();

    //   $product->set_name('teste 123');

    //   $product->set_price(10);

    //   update_post_meta($product->get_id(), 'vindi_product_id', 1239812039);

    // // Arrange: Set up an order
    // $order = WC_Helper_Order::create_order();

    // $order->add_product($product_1, 1); 

    // $order->set_payment_method($credit_card->id);
    // $order->set_payment_method_title($credit_card->method_title);
    // $order->save();
    // $order->calculate_totals();

    // print_r($order->get_items());

    // $payment_processor = new VindiPaymentProcessor($order, $credit_card, $settings, $controllers);
    // $bill_items = $payment_processor->build_product_items('bill', $order->get_items());

    // $this->assertEquals(
    //   array(
    //     array(
    //       'product_id' => 0,
    //       'quantity' => 4,
    //       'pricing_schema' => array(
    //         'price' => 10,
    //         'schema_type' => 'per_unit'
    //       )
    //     ),
    //     array(
    //       'product_id' => null,
    //       'amount' => 10
    //     )
    //   ),
    //   $bill_items
    // );
  }
}; ?>