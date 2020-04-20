<?php

include_once VINDI_PATH . 'src/services/VindiHelpers.php';
require_once VINDI_PATH . 'src/routes/RoutesApi.php';
require_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

include_once VINDI_PATH . 'src/includes/admin/Settings.php';

/**
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
    // 1) A variation product.    // 2) The same product added several times.
    // 3) A valid BR ZIP code
    $order = new WC_Order();

    $order->set_shipping_postcode('82540014');
    $order->add_product($product_1, 1); // Add one item of the first product variation
    $order->add_product($product_2, 2); // Add two items of the second product variation

    $order->save();
    $order->calculate_totals();

    // Act: Call get_level3_data_from_order().

    $settings = new VindiSettings();

    $gateway = new VindiCreditGateway($settings);
    $result = $gateway->get_level3_data_from_order($order);


    // Assert.
    $this->assertEquals(
      array(
        'merchant_reference' => $order->get_id(),
        'shipping_address_zip' => $order->get_shipping_postcode(),
        'shipping_from_zip' => $store_postcode,
        'shipping_amount' => 0,
        'line_items' => array(
          (object) array(
            'product_code'        => (string) $product_1->get_id(),
            'product_description' => substr($product_1->get_name(), 0, 26),
            'unit_cost'           => 1183,
            'quantity'            => 1,
            'tax_amount'          => 0,
            'discount_amount'     => 0,
          ),
          (object) array(
            'product_code'        => (string) $product_2->get_id(),
            'product_description' => substr($product_2->get_name(), 0, 26),
            'unit_cost'           => 2005,
            'quantity'            => 2,
            'tax_amount'          => 0,
            'discount_amount'     => 0,
          ),
        ),
      ),
      $result
    );
  }

  public function test_pre_30_postal_code_omission()
  {
    if (!VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), false);
      return;
    }

    $order = new WC_Order();
    $gateway = new VindiHelpers();
    $this->assertEquals(array(), $gateway->get_level3_data_from_order($order));
  }

  public function test_non_us_shipping_zip_codes()
  {
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    // Update the store with the right post code.
    update_option('woocommerce_store_postcode', 1040);

    // Arrange: Create a couple of products to use.
    $product = WC_Helper_Product::create_simple_product();
    $product->set_regular_price(19.19);
    $product->save();

    // Arrange: Set up an order with a non-US postcode.
    $order = new WC_Order();
    $order->set_shipping_postcode('1050');
    $order->add_product($product, 1);
    $order->save();
    $order->calculate_totals();

    // Act: Call get_level3_data_from_order().
    $store_postcode = '1100';

    $settings = new VindiSettings();

    $gateway = new VindiCreditGateway($settings);
    $result = $gateway->get_level3_data_from_order($order);

    // Assert.
    $this->assertEquals(
      array(
        'merchant_reference' => $order->get_id(),
        'shipping_amount' => 0,
        'line_items' => array(
          (object) array(
            'product_code'        => (string) $product->get_id(),
            'product_description' => substr($product->get_name(), 0, 26),
            'unit_cost'           => 1919,
            'quantity'            => 1,
            'tax_amount'          => 0,
            'discount_amount'     => 0,
          ),
        ),
      ),
      $result
    );
  }
};
