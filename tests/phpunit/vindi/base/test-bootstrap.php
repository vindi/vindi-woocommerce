<?php

include_once VINDI_PATH . 'src/VindiWoocommerce.php';

class Vindi_Test_Bootstrap extends Vindi_Test_Base
{

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    remove_action('admin_init', '_maybe_update_themes');
    remove_action('admin_init', '_maybe_update_core');
    remove_action('admin_init', '_maybe_update_plugins');

    // Make sure the main class is running
    WC_Vindi_Payment::instance();

    // Run fake actions
    do_action('plugins_loaded');
    do_action('init');
  }


  public function test_getInstance()
  {
    $this->assertInstanceOf('WC_Vindi_Payment', WC_Vindi_Payment::instance());
  }
}
