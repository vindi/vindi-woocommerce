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
    $coupon_code  = wc_format_coupon_code( $post->post_title );
    $id_from_code = wc_get_coupon_id_by_code( $coupon_code, $post_id );

		if ( $id_from_code ) {
			WC_Admin_Meta_Boxes::add_error( __( 'Coupon code already exists - customers will use the latest coupon with this code.', 'woocommerce' ) );
    }
		$coupon = new WC_Coupon( $post_id );
		$coupon->update_meta_data('cicle_count', intval($_POST['cicle_count']));
		$coupon->save();
  }
}
