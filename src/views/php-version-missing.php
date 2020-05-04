<?php
/**
 * Admin View: Notice - Warning when the site doesn't have the minimum required PHP version.
 */

if (!defined('ABSPATH')) {
    exit;
}
$message = sprintf(esc_html__('A Vindi precisa da versão %s+ do PHP. Por você não estar em uma versão mais recente, o plugin NÂO ESTÁ RODANDO atualmente.', VINDI), VINDI_MININUM_PHP_VERSION);
?>
<div class="error">
	<p>
    <strong>
      <?php _e('ERRO', VINDI);?>
    </strong>:
    <?php echo $message; ?>
    </p>
</div>
