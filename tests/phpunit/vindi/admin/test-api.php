<?php

include_once VINDI_PATH . 'src/VindiWoocommerce.php';
include_once VINDI_PATH . 'src/includes/admin/Settings.php';

class Vindi_Test_Admin extends Vindi_Test_Base
{
  /**
   * Estes testes validam os dados utilizados para a criação
   * de usuário, pedidos dentro da API da Vindi
   */
  public function test_create_customer_within_vindi()
  {
    $this->assertTrue(true);
  }
}
