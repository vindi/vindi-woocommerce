<?php
/**
 * Admin View: Notice - Warning when the site doesn't have the minimum required WordPress version.
 */

if (!defined('ABSPATH')) {
    exit;
}

$message = sprintf(esc_html__('Vindi requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', VINDI), VINDI_MININUM_WP_VERSION);
?>
<div class="error">
	<p>
    <strong>
      <?php _e('ERROR', VINDI);?>
    </strong>:
    <?php echo $message; ?>
    </p>
</div>
