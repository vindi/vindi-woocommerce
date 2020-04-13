<?php if (!defined('ABSPATH')) exit; ?>

<?php if ($testmode): ?>
  <p>TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.</p>
<?php endif; ?>
<?php if ($description): ?>
  <p><?php echo $description; ?></p>
<?php endif; ?>

<fieldset id="wc-<?php echo $id; ?>-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">

    <?php do_action('woocommerce_credit_card_form_start', $id); ?>

    <div class="form-row form-row-wide">
      <label><?php _e('Card Number', VINDI); ?> <span class="required">*</span></label>
      <input id="vindi_ccNo" type="text" autocomplete="off">
    </div>
    <div class="form-row form-row-first">
      <label><?php _e('Expiry Date', VINDI); ?> <span class="required">*</span></label>
      <input id="vindi_expdate" type="text" autocomplete="off" placeholder="MM / YY">
    </div>
    <div class="form-row form-row-last">
      <label><?php _e('Card Code (CVC)', VINDI); ?> <span class="required">*</span></label>
      <input id="vindi_cvv" type="password" autocomplete="off" placeholder="CVC">
    </div>
    <div class="clear"></div>

    <?php do_action('woocommerce_credit_card_form_end', $id); ?>

    <div class="clear"></div>
</fieldset>
