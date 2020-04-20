<?php if (!defined( 'ABSPATH')) exit; ?>

<?php if (!$gateway->vindi_settings->check_ssl()): ?>
  <div class="error">
    <p>
      <strong><?php _e('Vindi WooCommerce Desabilitado', VINDI); ?></strong>:
      <?php printf(__('É necessário um <strong> Certificado SSL </strong> para ativar este método de pagamento em modo de produção. Por favor, verifique se um certificado SSL está instalado em seu servidor !')); ?>
    </p>
  </div>
<?php endif; ?>

<h3><?php _e('Vindi', VINDI); ?></h3>
<p><?php _e('Utiliza a rede Vindi como meio de pagamento para cobranças.', VINDI); ?></p>
<table class="form-table">
  <?php $gateway->generate_settings_html(); ?>
</table>