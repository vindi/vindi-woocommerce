<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/routes/RoutesApi.php';
include_once VINDI_PATH . 'src/controllers/ProductController.php';
include_once VINDI_PATH . 'src/utils/PaymentProcessor.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/services/VindiHelpers.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Product_Controller extends Vindi_Test_Base
{
  private $mock_data;
  public function setUp()
  {
    $this->mock_data = array(
      'id' => 463389,
      'name' => '[WC] Dummy Product',
      'code' => 'WC-10',
      'unit' => null,
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
  }

  public function tearDown()
  {
    $this->mock_data = null;
  }

  public function test_create_product()
  {
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createProduct')->willReturn($this->mock_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $product_controller = new ProductController($settings);

    $product = WC_Helper_Product::create_simple_product();
    $product->delete_meta_data('vindi_product_id');
    $product->save();

    $createdProduct = $product_controller->create($product->get_id(), '', '', true);
    $this->assertEquals($createdProduct, $this->mock_data);
  }

  public function test_update_product()
  {
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createProduct')->willReturn($this->mock_data);
    $routes->method('updateProduct')->willReturn($this->mock_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $product_controller = new ProductController($settings);

    $product = WC_Helper_Product::create_simple_product();

    $createdProduct = $product_controller->update($product->get_id(), '', '', true);
    $this->assertEquals($createdProduct, $this->mock_data);
  }

  public function test_untrash_product()
  {
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createProduct')->willReturn($this->mock_data);
    $routes->method('updateProduct')->willReturn($this->mock_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $product_controller = new ProductController($settings);

    $product = WC_Helper_Product::create_simple_product();

    $createdProduct = $product_controller->untrash($product->get_id());
    $this->assertEquals($createdProduct, $this->mock_data);
  }

  public function test_trash_product()
  {
    // Skip this test because of the complexity of creating products in WC pre-3.0.
    if (VindiHelpers::is_wc_lt('3.0')) {
      // Dummy assertion.
      $this->assertEquals(VindiHelpers::is_wc_lt('3.0'), true);
      return;
    }

    $trash_data = $this->mock_data;
    $trash_data['status'] = 'inactive';

    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createProduct')->willReturn($this->mock_data);
    $routes->method('updateProduct')->willReturn($trash_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $product_controller = new ProductController($settings);

    $product = WC_Helper_Product::create_simple_product();

    $createdProduct = $product_controller->trash($product->get_id());
    $this->assertEquals($createdProduct, $trash_data);
  }
}; ?>