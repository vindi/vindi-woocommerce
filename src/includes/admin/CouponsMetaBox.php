<?php

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
	 * @param WP_Post $post
	 */
  public static function output( $coupon_id, $coupon )
  {
		woocommerce_wp_select(
			array(
				'id'      => 'cicle_count',
				'label'   => __( 'Discount coupon cicle count', 'woocommerce' ),
				'value'   => get_post_meta($coupon->get_id(), 'cicle_count')[0],
				'options' => array(
					'0'  => 'All cicles',
					'1'  => '1 cicle',
					'2'  => '2 cicles',
					'3'  => '3 cicles',
					'4'  => '4 cicles',
					'5'  => '5 cicles',
					'6'  => '6 cicles',
					'7'  => '7 cicles',
					'8'  => '8 cicles',
					'9'  => '9 cicles',
					'10' => '10 cicles',
					'11' => '11 cicles',
					'12' => '12 cicles',
				),
			)
		);
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
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
			return;
		}
		$coupon = new WC_Coupon( $post_id );
		$coupon->update_meta_data('cicle_count', intval($_POST['cicle_count']));
		$coupon->save();
  }

  /**
	 * Remove Woocommerce Subscriptions recurring discount options.
	 * This is done to force the user to select a vindi cicle count discount
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
  public static function remove_ws_recurring_discount($discount_types)
  {
    return array_diff(
			$discount_types,
			array(
				'recurring_fee'       => __( 'Recurring Product Discount', 'woocommerce-subscriptions' ),
				'recurring_percent'   => __( 'Recurring Product % Discount', 'woocommerce-subscriptions' ),
			)
		);
  }
}
