<?php
/**
 * Admin View: Notice - Invalid token message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="notice notice-error is-dismissible">
	<p><strong><?php _e( 'Invalid Token', 'vindi-woocommerce' ); ?></strong>: <?php printf( __( 'The provided API token is not a valid Vindi API token! Please check your spelling and try again!', 'vindi-woocommerce' ) ); ?></p>
</div>
