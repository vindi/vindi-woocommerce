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

  /**
   * @var PlansController
   */
  public $plans;

  /**
   * @var CustomerController
   */
   public $customers;

  function __construct(VindiSettings $vindi_settings)
  {
    $this->includes();

    $this->plans = new PlansController($vindi_settings);
    $this->customers = new CustomerController($vindi_settings);
  }


  function includes()
  {
    require_once WC_Vindi_Payment::getPath() . '/controllers/PlansController.php';
    require_once WC_Vindi_Payment::getPath() . '/controllers/CustomerController.php';
  }
}
