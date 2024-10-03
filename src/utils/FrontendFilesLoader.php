<?php

namespace VindiPaymentGateways;

if (! defined('ABSPATH')) {
  die();
	exit; // Exit if accessed directly
}
use WC_Subscriptions_Product;

class FrontendFilesLoader {

  function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'frontendFiles'));
    add_action('admin_enqueue_scripts', array($this, 'adminFiles'));
        add_action('wp_enqueue_scripts', [$this, 'enqueue_inputmask_scripts']);
        add_action('add_meta_boxes', array($this, 'check_for_subscription_in_order'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_payment_link_generator_script'));
  }

  public static function adminFiles()
  {
        $dir_path = plugins_url('/assets/js/editpost.js', plugin_dir_path(__FILE__));
    wp_register_script('jquery-mask', plugins_url('/assets/js/jquery.mask.min.js', plugin_dir_path(__FILE__)), array('jquery'), VINDI_VERSION, true);
    wp_register_script('vindi_woocommerce_admin_js', plugins_url('/assets/js/admin.js', plugin_dir_path(__FILE__)), array('jquery', 'jquery-mask'), VINDI_VERSION, true);
    wp_enqueue_script('vindi_woocommerce_admin_js');
    wp_register_style('vindi_woocommerce_admin_style', plugins_url('/assets/css/admin.css', plugin_dir_path(__FILE__)), array(), VINDI_VERSION);
    wp_enqueue_style('vindi_woocommerce_admin_style');
        wp_enqueue_script("vindi_products", plugins_url('/assets/js/product.js', plugin_dir_path(__FILE__)));
        wp_register_script('vindi_woocommerce_edit_js', $dir_path, array('jquery'), VINDI_VERSION, true);
        wp_enqueue_script('vindi_woocommerce_edit_js');
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
            array('ajax_url' => admin_url('admin-ajax.php'))
        );
    }

    public static function enqueueCreditCardScripts()
    {
        wp_register_script(
            'imask',
            plugins_url('/assets/js/imask.min.js', plugin_dir_path(__FILE__)),
            array(),
            VINDI_VERSION,
            true
        );

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

    public function enqueue_inputmask_scripts()
    {
        $cdnInput = 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js';
        wp_enqueue_script('inputmask', $cdnInput, array('jquery'), '5.0.8', true);
        wp_add_inline_script('inputmask', '
            jQuery(document).ready(function($) {
                $("#billing_phone").inputmask("(99) 99999-9999");
                $("#billing_postcode").inputmask("99999-999");
            });
        ');
    }

    public function check_for_subscription_in_order()
    {
        global $post;
        if ($this->is_shop_order_or_subscription($post)) {
            $has_subscription = $this->order_has_subscription($post->ID);
            $this->enqueue_notification_script($has_subscription);
        }
    }
    
    private function is_shop_order_or_subscription($post)
    {
        return $post->post_type === 'shop_order' || $post->post_type === 'shop_subscription';
    }

    private function order_has_subscription($order_id)
    {
        $order = wc_get_order($order_id);
        $subscriptions_product = new WC_Subscriptions_Product();
        
        foreach ($order->get_items() as $item) {
            if ($subscriptions_product->is_subscription($item->get_product())) {
                return true;
            }
        }
        
        return false;
    }
    
    private function enqueue_notification_script($has_subscription)
    {
        $dir_path = plugins_url('/assets/js/notification.js', plugin_dir_path(__FILE__));
        wp_register_script('notification-js', $dir_path, array('jquery'), VINDI_VERSION, true);
        wp_enqueue_script('notification-js');
        wp_localize_script('notification-js', 'orderItem', array(
            'hasSubscription' => $has_subscription
        ));
    }

    public function enqueue_payment_link_generator_script()
    {
        $post = get_post_type();
        $dir_path = plugins_url('/assets/js/edit.js', plugin_dir_path(__FILE__));
        wp_register_script('edit-js', $dir_path, array('jquery'), VINDI_VERSION, true);
        wp_enqueue_script('edit-js');
        
        wp_localize_script('edit-js', 'orderData', array(
            'typePost' => $post
        ));
    }
}
