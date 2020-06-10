<?php

include_once VINDI_PATH . 'src/includes/admin/Settings.php';
include_once VINDI_PATH . 'src/controllers/index.php';
include_once VINDI_PATH . 'src/utils/PaymentGateway.php';
include_once VINDI_PATH . 'src/includes/gateways/BankSlipPayment.php';
include_once VINDI_PATH . 'src/includes/gateways/CreditPayment.php';

class Vindi_Test_Gateways extends Vindi_Test_Base
{
  public function test_gateways_type()
  {
    $settings = new VindiSettings();
    $controllers = new VindiControllers($settings);

    $credit_card = new VindiCreditGateway($settings, $controllers);
    $bank_slip = new VindiBankSlipGateway($settings, $controllers);
    $this->assertEquals('cc', $credit_card->type());
    $this->assertEquals('bank_slip', $bank_slip->type());
  }
}; ?>