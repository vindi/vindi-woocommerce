<?php

namespace VindiPaymentGateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class VindiProductStatus
{

  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  function __construct(VindiSettings $vindi_settings)
  {
    $this->vindi_settings = $vindi_settings;
    
    add_action('admin_notices', array(&$this, 'product_status_notifier'));
  }
  
  /**
   * Show product creation status
   * @return string $text
   */
  public function product_status_notifier()
  {
    if(empty(get_transient('vindi_product_message')))
      return;

    include_once VINDI_SRC . 'views/product-status.php';
  }
}
