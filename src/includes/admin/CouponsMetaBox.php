<?php

namespace VindiPaymentGateways;

use WC_Coupon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Coupon_Data Class updated with custom fields.
 */
class CouponsMetaBox {

    /**
     * Output the metabox.
     *
     * @param int $coupon_id
     * @param WC_Coupon $coupon
     */
    public static function output($coupon_id, $coupon)
    {
        $arr = array(
            'id'      => 'cycle_count',
            'label'   => __('Número de ciclos do cupom', VINDI),
            'value'   => get_post_meta($coupon_id, 'cycle_count')[0],
            'options' => array(
              '0'  => 'Todos os ciclos',
              '1'  => '1 ciclo',
              '2'  => '2 ciclos',
              '3'  => '3 ciclos',
              '4'  => '4 ciclos',
              '5'  => '5 ciclos',
              '6'  => '6 ciclos',
              '7'  => '7 ciclos',
              '8'  => '8 ciclos',
              '9'  => '9 ciclos',
              '10' => '10 ciclos',
              '11' => '11 ciclos',
              '12' => '12 ciclos',
            ),
        );

        if ($coupon->get_discount_type() == 'recurring_percent') {
            array_push($arr, 'class', 'hidden');
        }

        woocommerce_wp_select($arr);
    }

    /**
     * Save meta box custom fields data.
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public static function save($post_id, $post)
    {
        // Check the nonce (again).
        if (empty(VindiHelpers::sanitize_xss($_POST['woocommerce_meta_nonce'])) ||
            !wp_verify_nonce(VindiHelpers::sanitize_xss($_POST['woocommerce_meta_nonce']), 'woocommerce_save_data')) {
            return;
        }
        $coupon = new WC_Coupon($post_id);
        $coupon->update_meta_data('cycle_count', intval(filter_var($_POST['cycle_count'], FILTER_SANITIZE_NUMBER_INT)));
        $coupon->save();
    }

    /**
     * Remove Woocommerce Subscriptions recurring discount options.
     * This is done to force the user to select a vindi cicle count discount
     *
     * @param array $discount_types
     */
    public static function remove_ws_recurring_discount($discount_types)
    {
        return array_diff(
            $discount_types,
            array(
              'sign_up_fee'         => __('Sign Up Fee Discount', 'woocommerce-subscriptions'),
              'sign_up_fee_percent' => __('Sign Up Fee % Discount', 'woocommerce-subscriptions'),
              'recurring_fee'       => __('Recurring Product Discount', 'woocommerce-subscriptions')
            )
        );
    }
}
