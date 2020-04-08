<?php if (!defined('ABSPATH')) exit; ?>

<?php if ($testmode): ?>
  <p>TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.</p>
<?php endif; ?>
<?php if ($description): ?>
  <p><?php echo $description; ?></p>
<?php endif; ?>

<fieldset id="wc-<?php echo $id; ?>-form" class="wc-bankslip-form wc-payment-form" style="background:transparent;">

    <?php do_action('woocommerce_bank_slip_form_start', $id); ?>

    <div class="form-row form-row-wide">
      <?php _e('A Bank Slip wil be sent to your e-mail.', VINDI); ?>
      <?php //_e('Um Boleto Bancário será enviado para o seu endereço de e-mail.', VINDI); ?>
    </div>
    <div class="clear"></div>

    <?php do_action('woocommerce_bank_slip_form_end', $id); ?>

    <div class="clear"></div>
</fieldset>
