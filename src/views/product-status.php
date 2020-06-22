<?php
/**
 * Admin View: Notice - Invalid token message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = get_transient('vindi_product_message');
?>

<?php if ($type == 'created'): ?>
  <div class="vindi-alert notice vindi-success">
		<p><strong><?php _e('Vindi', VINDI);?></strong>: <?php printf( __( 'O produto foi criado na Vindi com sucesso!', VINDI ) ); ?></p>
	</div>
<?php elseif ($type == 'updated'): ?>
  <div class="vindi-alert notice vindi-success">
		<p><strong><?php _e('Vindi', VINDI);?></strong>: <?php printf( __( 'O produto foi atualizado na Vindi com sucesso!', VINDI ) ); ?></p>
	</div>
<?php else: ?>
  <div class="vindi-alert notice vindi-error">
		<p><strong><?php _e('Vindi', VINDI);?></strong>: <?php printf( __( 'Não foi possível criar/atualizar o produto na Vindi!', VINDI ) ); ?></p>
	</div>
<?php
	endif;

	delete_transient('vindi_product_message');
?>


