<?php
/**
 * Admin View: Notice - Invalid token message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="notice notice-error is-dismissible">
	<p><strong><?php _e( 'Chave API Inválida', VINDI ); ?></strong>: <?php printf( __( 'A chave API utilizada não é uma chave válida! Por favor verifique as informações e tente novamente!', VINDI ) ); ?></p>
</div>
