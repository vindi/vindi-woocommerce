<?php

/**
*  In these tests we address the issues related to the API, so for it to work it
*  is necessary to add the token inside the .env.php file, if this file does not
*  exist, use the .env.example.php as a base
*
* @since 1.0.0
* @version 1.0.0
*/

include_once VINDI_PATH . 'src/services/Logger.php';
include_once VINDI_PATH . 'src/services/Api.php';

include_once VINDI_PATH. 'src/routes/RoutesApi.php';

include_once VINDI_PATH . 'src/includes/admin/Settings.php';


class Vindi_Test_Api extends Vindi_Test_Base
{

  /**
   * This test validates the token inserted into the .env.php file
   */
  public function test_api_token()
  {
    if (!defined('API_TEST') || !API_TEST) {

      $this->assertTrue(true);
      return;
    }

    $logger = new VindiLogger(VINDI, false);

    $api = new VindiApi('', $logger, '');

    $this->assertEquals('1', $api->test_api_key(API_TOKEN));
  }

  /**
   * This test validates the data used for the creation
   * orders within the Vindi API
   */

  public function test_create_customer_within_vindi()
  {

    if(!defined('API_TEST') || !API_TEST) return;

    $vindi_customer_id = get_user_meta(1, 'vindi_customer_id');

    // Check meta Vindi ID
    if (empty($vindi_customer_id)) {

      // Criando usuário dentro da vindi
      $createdUser = $this->routes->createCustomer(
        array(
          'name' => 'Teste de criação de usuário',
          'email' => 'admin@teste.com',
          'code' => 'WC-' . $user['id'],
          'address' => array(
            'street' => 'Flávio dallegrave',
            'number' => '0',
            'registry_code' => '00000000000',
            'additional_details' => '',
            'zipcode' => '',
            'neighborhood' => '',
            'city' => '',
            'state' => '',
            'country' => ''
          ),
          'phones' => array(
            array(
              'phone_type' => 'mobile',
              'number' => '5599999999999',
            ),
            array(
              'phone_type' => 'landline',
              'number' => '559999999999',
            )
          ),
          'registry_code' => '',
          'notes' => '',
          'metadata' => '',
        )
      );

      print_r($createdUser);
    }

  }

  /**
   * Este teste valida os dados utilizados para a criação
   * de um plano da API da Vindi
   */
  public function test_create_info_plan_within_vindi()
  {
    $this->assertTrue(true);
  }
}
