<?php

class SupportSubscriptions {

  function __construct() {
    $this->includes();

    $this->credit = new WC_Vindi_Credit_Gateway();
  }

  public function includes() {
    require_once WC_Vindi_Payment::getPath() . '/includes/gateways/CreditPayment.php';

  }
}

;
