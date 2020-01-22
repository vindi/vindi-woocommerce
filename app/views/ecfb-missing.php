<?php
/**
 * Admin View: Notice - WooCommerce Extra Checkout Fields for Brazil missing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_slug = 'woocommerce-extra-checkout-fields-for-brazil';

if ( current_user_can( 'install_plugins' ) ) {
	$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
} else {
	$url = 'http://wordpress.org/plugins/' . $plugin_slug;
}
?>

<div class="error">
	<p><strong><?php _e( 'vindi disabled', 'vindi-woocommerce' ); ?></strong>: <?php printf( __( 'WooCommerce vindi requires the latest version of %s to work!', 'vindi-woocommerce' ), '<a href="' . esc_url( $url ) . '">' . __( 'WooCommerce Extra Checkout Fields for Brazil', 'vindi-woocommerce' ) . '</a>' ); ?></p>
</div>
