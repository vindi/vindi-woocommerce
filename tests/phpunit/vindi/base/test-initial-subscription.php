<?php

include_once VINDI_PATH . 'src/helpers/VindiHelpers.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';


/**
 * These tests assert various things about processing an initial payment
 * for a WooCommerce Subscriptions.
 *
 * The responses from HTTP requests are mocked using the WP filter
 * `pre_http_request`.
 *
 */

class Vindi_Test_Subscription_initial extends Vindi_Test_Base
{
  function test_data_for_mutli_item_order()
  {

    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $store_postcode = '82540014';
    update_option('woocommerce_store_postcode', $store_postcode);

    // Arrange: Create a couple of products to use.
    $variation_product = WC_Helper_Product::create_variation_product();
    $variation_ids     = $variation_product->get_children();

    $product_1 = wc_get_product($variation_ids[0]);
    $product_1->set_regular_price(19.19);
    $product_1->set_sale_price(11.83);
    $product_1->save();

    $product_2 = wc_get_product($variation_ids[1]);
    $product_2->set_regular_price(20.05);
    $product_2->save();

    // Arrange: Set up an order with:
    // 1) A variation product.
    // 2) The same product added several times.
    // 3) A valid BR ZIP code
    $order = new WC_Order();
    $order->set_shipping_postcode('82540014');
    $order->add_product($product_1, 1); // Add one item of the first product variation
    $order->add_product($product_2, 2); // Add two items of the second product variation

    $order->save();
    $order->calculate_totals();

    // Act: Call get_level3_data_from_order().
    $gateway = new VindiPaymentGateway();
    $result = $gateway->get_level3_data_from_order($order);


    print_r($result);
  }
};
