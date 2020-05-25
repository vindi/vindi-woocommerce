<?php

if (! defined('ABSPATH')) {
  die();
	exit; // Exit if accessed directly
}

class FrontendFilesLoader {

  function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'frontendFiles'));
    add_action('admin_enqueue_scripts', array($this, 'adminFiles'));
  }

  public static function adminFiles()
  {
    wp_register_script('jquery-mask', plugins_url('/assets/js/jquery.mask.min.js', plugin_dir_path(__FILE__)), array('jquery'), VINDI_VERSION, true);
    wp_register_script('vindi_woocommerce_admin_js', plugins_url('/assets/js/admin.js', plugin_dir_path(__FILE__)), array('jquery', 'jquery-mask'), VINDI_VERSION, true);
    wp_enqueue_script('vindi_woocommerce_admin_js');
  }
  public static function frontendFiles()
  {
    wp_register_script('vindi_woocommerce_frontend_js', plugins_url('/assets/js/frontend.js', plugin_dir_path(__FILE__)), array('jquery', 'jquery-payment'), VINDI_VERSION, true);
    wp_enqueue_script('vindi_woocommerce_frontend_js');
  }
}
