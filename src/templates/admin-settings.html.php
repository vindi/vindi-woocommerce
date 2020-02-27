<?php if (!defined( 'ABSPATH')) exit; ?>

<h3><?php _e('Vindi', VINDI); ?></h3>
<p><?php _e('Uses the Vindi network as a payment method for collections.', VINDI); ?></p>
<table class="form-table">
    <?php $settings->generate_settings_html(); ?>
</table>
