<?php
/**
 * Admin View: Notice - Invalid token message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="vindi-alert notice vindi-warning">
	<p><strong><?php _e( 'Chave API Inválida', VINDI ); ?></strong>: <?php printf( __( 'A chave API utilizada não é uma chave válida! Por favor verifique as informações e tente novamente!', VINDI ) ); ?></p>
</div>
