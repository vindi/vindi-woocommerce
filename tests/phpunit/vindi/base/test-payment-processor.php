<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/routes/RoutesApi.php';
include_once VINDI_PATH . 'src/controllers/index.php';
include_once VINDI_PATH . 'src/utils/PaymentProcessor.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/services/VindiHelpers.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Payment_Processor extends Vindi_Test_Base
{
  private $mock_shipping_product;
  private $customer;

  public function setUp() {
    $this->mock_shipping_product = array(
      'id' => 465,
      'name' => 'Frete (Flat rate)',
      'code' => 'flat-rate',
      'unit' => '',
      'status' => 'active',
      'description' => '',
      'invoice' => 'always',
      'created_at' => '2020-06-08T11:20:57.000-03:00',
      'updated_at' => '2020-06-08T11:20:57.000-03:00',
      'pricing_schema' => array(
        'id' => 'string',
        'short_format' => 'R$ 10,0',
        'price' => 10.0,
        'minimum_price' => null,
        'schema_type' => 'flat',
        'pricing_ranges' => array(),
        'created_at' => '2020-06-08T11:20:57.000-03:00'
      ),
      'metadata' => array()
    );
    $this->customer = WC_Helper_Customer::create_customer();
    update_user_meta($this->customer->get_id(), 'vindi_customer_id', 328876);
  }

  public function tearDown()
  {
    $this->mock_shipping_product = null;
    wp_delete_user($this->customer->get_id());
    wc_delete_user_data($this->customer->get_id());
    $this->customer = null;
  }

  public function test_processor_build_items()
  {
    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('findOrCreateProduct')->willReturn($this->mock_shipping_product);
    $settings = new VindiSettings();
    $settings->routes = $routes;
    $controllers = new VindiControllers($settings);

    $credit_card = new VindiCreditGateway($settings, $controllers);
    
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    // Arrange: Create a couple of products to use.
    // $simple_product = WC_Helper_Product::create_simple_product();

    $product = new WC_Product();

    $product->set_name('teste 123');

    $product->set_price(20);
    $product->save();

    update_post_meta($product->get_id(), 'vindi_product_id', 1239812039);

    // Arrange: Set up an order
    $order = WC_Helper_Order::create_order($this->customer->get_id());
    $order->remove_order_items('line_item');

    $order->add_product($product, 4, array(
      'subtotal' => 80,
      'total' => 80
    ));

    $order->set_payment_method($credit_card->id);
    $order->set_payment_method_title($credit_card->method_title);
    $order->save();
    $order->calculate_totals();

    $payment_processor = new VindiPaymentProcessor($order, $credit_card, $settings, $controllers);
    $bill_items = $payment_processor->build_product_items('bill', $order->get_items());

    $this->assertEquals(
      array(
        array(
          'product_id' => 63,
          'quantity' => 4,
          'pricing_schema' => array(
            'price' => 20.0,
            'schema_type' => 'per_unit'
          )
        ),
        array(
          'product_id' => 465,
          'amount' => 10.0
        )
      ),
      $bill_items
    );
  }
}; ?>
