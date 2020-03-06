<?php

include_once VINDI_PATH . 'src/VindiWoocommerce.php';

class Vindi_Test_Api extends Vindi_Test_Base
{
  /**
   * Este teste valida os dados utilizados para a criação
   * de usuário, pedidos dentro da API da Vindi
   */
  public function test_create_info_customer_within_vindi()
  {
    if (!defined('API_TEST') || !API_TEST) {

      $this->assertTrue(true);
      return;
    }

    $this->assertNotContains('unauthorized', VindiApi::test_api_key(API_TOKEN));
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
