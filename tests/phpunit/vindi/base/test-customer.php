<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/routes/RoutesApi.php';
include_once VINDI_PATH . 'src/controllers/CustomerController.php';
include_once VINDI_PATH . 'src/utils/PaymentProcessor.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/services/VindiHelpers.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Customer_Controller extends Vindi_Test_Base
{
  private $mock_data;
  private $customer;

  public function setUp()
  {
    $this->mock_data = array(
      'id' => 328876,
      'name' => 'Pedro Vicente Nicolas Pereira',
      'email' => 'pedro.vicente@woo.local',
      'code' => 'WC-USER-2',
      'status' => 'active',
      'address' => array(
        'street' => 'Rua Joaquim Mendes do Prado',
        'number' => '927',
        'additional_details' => 'Casa 1',
        'zipcode' => '03379-030',
        'neighborhood' => 'Vila Olinda',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR'
      ),
      'phones' => array(
        array(
          'id' => 6874,
          'phone_type' => 'landline',
          'number' => '551125939005'
        )
      ),
      'registry_code' => '274.587.488-85',
      'notes' => '',
      'metadata' => '',
      'created_at' => '2020-06-08T11:20:57.000-03:00',
      'updated_at' => '2020-06-08T11:20:57.000-03:00'
    );

    $this->customer = WC_Helper_Customer::create_customer();
  }

  public function tearDown()
  {
    $this->mock_data = null;
    wp_delete_user($this->customer->get_id());
    wc_delete_user_data($this->customer->get_id());
    $this->customer = null;
  }

  public function test_create_customer()
  {
    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createCustomer')->willReturn($this->mock_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $customer_controller = new CustomerController($settings);
    update_user_meta($this->customer->get_id(), 'vindi_customer_id', 328876);

    $createdUser = $customer_controller->create($this->customer->get_id());
    $this->assertEquals($createdUser, $this->mock_data);
  }

  public function test_update_customer()
  {
    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('createCustomer')->willReturn($this->mock_data);
    $routes->method('findCustomerById')->willReturn($this->mock_data);
    $routes->method('updateCustomer')->willReturn($this->mock_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $customer_controller = new CustomerController($settings);
    update_user_meta($this->customer->get_id(), 'vindi_customer_id', 328876);

    $createdUser = $customer_controller->update($this->customer->get_id());
    $this->assertEquals($createdUser, $this->mock_data);
  }

  public function test_delete_customer()
  {
    $deleted_data = $this->mock_data;
    $deleted_data['status'] = 'archived';
    $routes = $this->createMock(VindiRoutes::class);
    $routes->method('findCustomerById')->willReturn($this->mock_data);
    $routes->method('createCustomer')->willReturn($this->mock_data);
    $routes->method('deleteCustomer')->willReturn($deleted_data);
    $settings = $this->createMock(VindiSettings::class);
    $settings->routes = $routes;

    $customer_controller = new CustomerController($settings);
    update_user_meta($this->customer->get_id(), 'vindi_customer_id', 328876);

    $deletedUser = $customer_controller->delete($this->customer->get_id());
    $this->assertEquals($deletedUser, $deleted_data);
  }
}; ?>