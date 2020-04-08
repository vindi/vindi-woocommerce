<?php

if ( ! defined( 'ABSPATH' ) ) {
  die();
	exit; // Exit if accessed directly
}

class FrontendFilesLoader {

  function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'registerFiles'));
  }

  public static function registerFiles()
  {
    wp_register_script( 'vindi_woocommerce_frontend_js', plugins_url( '/assets/js/frontend.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-payment' ), VINDI_VERSION, true );
    wp_enqueue_script ( 'vindi_woocommerce_frontend_js' );
  }
}
