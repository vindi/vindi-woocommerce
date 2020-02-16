<?php
/**
 * Admin View: Notice - Warning when the site doesn't have the minimum required PHP version.
 */

if (!defined('ABSPATH')) {
    exit;
}
$message = sprintf(esc_html__('Vindi requires PHP version %s+, plugin is currently NOT RUNNING.', VINDI), VINDI_MININUM_PHP_VERSION);
?>
<div class="error">
	<p>
    <strong>
      <?php _e('ERROR', VINDI);?>
    </strong>:
    <?php echo $message; ?>
    </p>
</div>
