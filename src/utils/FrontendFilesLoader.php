<?php

namespace VindiPaymentGateways;

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
    wp_register_style('vindi_woocommerce_admin_style', plugins_url('/assets/css/admin.css', plugin_dir_path(__FILE__)), array(), VINDI_VERSION);
    wp_enqueue_style('vindi_woocommerce_admin_style');
        wp_enqueue_script("vindi_products", plugins_url('/assets/js/product.js', plugin_dir_path(__FILE__)));
  }
    public static function frontendFiles()
    {
        wp_register_script(
            'vindi_woocommerce_frontend_js',
            plugins_url('/assets/js/frontend.js', plugin_dir_path(__FILE__)),
            array('jquery'),
            VINDI_VERSION,
            true
        );
        wp_enqueue_script('vindi_woocommerce_frontend_js');

        self::enqueueCreditCardScripts();
        self::enqueueThankyouPageScript();

        wp_register_style(
            'vindi_woocommerce_style',
            plugins_url('/assets/css/frontend.css', plugin_dir_path(__FILE__)),
            array(),
            VINDI_VERSION
        );
        wp_enqueue_style('vindi_woocommerce_style');
    }

    public static function enqueueThankyouPageScript()
    {
        wp_register_script(
            'vindi_woocommerce_thankyou_js',
            plugins_url('/assets/js/thankyou.js', plugin_dir_path(__FILE__)),
            array(),
            VINDI_VERSION,
            true
        );
        wp_enqueue_script('vindi_woocommerce_thankyou_js');

        wp_localize_script(
            'vindi_woocommerce_thankyou_js',
            'ajax_object',
            array('ajax_url' => admin_url( 'admin-ajax.php' ))
        );
    }

    public static function enqueueCreditCardScripts()
    {
        wp_register_script('imask', plugins_url('/assets/js/imask.min.js', plugin_dir_path(__FILE__)), array(), VINDI_VERSION, true);

        wp_register_script(
            'vindi_woocommerce_masks_js',
            plugins_url('/assets/js/masks.js', plugin_dir_path(__FILE__)),
            array('imask'),
            VINDI_VERSION,
            true
        );
        wp_enqueue_script('vindi_woocommerce_masks_js');

        wp_register_script(
            'vindi_woocommerce_brands_js',
            plugins_url('/assets/js/brands.js', plugin_dir_path(__FILE__)),
            array(),
            VINDI_VERSION,
            true
        );
        wp_enqueue_script('vindi_woocommerce_brands_js');
    }
}