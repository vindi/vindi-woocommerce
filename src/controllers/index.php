<?php

/**
 * Merging all the controllers needed to
 * communication between the Vindi API.
 *
 * @return void;
 */

class VindiControllers
{

  /**
   * @var string
   */
  private $path;


  function __construct()
  {
    $this->includes();

    $this->plans = new PlansController();
    $this->customers = new CostumerController();
  }


  function includes()
  {
    require_once WC_Vindi_Payment::getPath() . '/controllers/PlansController.php';
    require_once WC_Vindi_Payment::getPath() . '/controllers/CustomerController.php';
  }
}
