<?php if(!defined( 'ABSPATH')) exit; ?>

<?php if(!$settings->check_ssl()): ?>
<div class="error">
  <p>
    <strong><?php _e('Vindi WooCommerce Desabilitado', VINDI); ?></strong>:
    <?php printf(__('É necessário um <strong>Certificado SSL</strong> para ativar este método de pagamento em modo de produção. Por favor, verifique se um certificado SSL está instalado em seu servidor!', VINDI)); ?>
  </p>
</div>
<?php endif; ?>

<h3><?php _e('Vindi', VINDI); ?></h3>
<p><?php _e('Uses the Vindi network as a payment method for collections.', VINDI); ?></p>
<table class="form-table">
  <?php $settings->generate_settings_html(); ?>
</table>
<div class="below-h2 <?php echo $merchant !== false ? 'updated' : 'error'; ?>">
	<h3 class="wc-settings-sub-title">
		<?php _e('Link de configuração dos Eventos da Vindi', VINDI); ?>
	</h3>

	<p><?php _e('Copie esse link e utilize-o para configurar os eventos nos Webhooks da Vindi.', VINDI); ?></p>

	<p>
		<input type="text" value="<?php echo $settings->get_webhooks_url(); ?>" readonly="readonly" style="width:100%;"
      onClick="this.select(); this.setSelectionRange(0, this.value.length); document.execCommand('copy');"/>
	</p>

	<h3 class="wc-settings-sub-title">
		<?php _e( 'Teste de conexão com a Vindi', VINDI); ?>
	</h3>

  <?php
    $merchant = false;
    $api_key  = $settings->get_api_key();
    if(!empty($api_key))
        $merchant = $settings->routes->getMerchant(true);
  ?>

	<div>
    <?php
      if($merchant):
    ?>
        <p><?php _e('Conectado com sucesso!', VINDI) ?></p>
        <p><?php echo sprintf(__('Conta: <strong>%s</strong>.', VINDI), $merchant['name']) ?></p>
        <p><?php echo sprintf(__('Status: <strong>%s</strong>.', VINDI), ucwords($merchant['status'])) ?></p>
    <?php
      else:
        echo sprintf(__('<p>Falha na conexão! <br><b>%s</b></p>', VINDI), $settings->api->last_error);
      endif; 
    ?>
	</div>
</div>