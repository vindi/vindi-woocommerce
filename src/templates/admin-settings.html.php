<?php if (!defined('ABSPATH')) {
    exit;
}
if (!$settings->check_ssl()): ?>
<div class="error">
  <p>
    <strong><?php _e('Vindi WooCommerce Desabilitado', VINDI);?></strong>:
    <?php printf(__('É necessário um <strong>Certificado SSL</strong> para ativar este método de pagamento em modo de produção. Por favor, verifique se um certificado SSL está instalado em seu servidor!', VINDI));?>
  </p>
</div>
<?php endif;?>

<h3><?php _e('Vindi', VINDI);?></h3>
<p><?php _e('Utiliza a rede Vindi como meio de pagamento para cobranças.', VINDI);?></p>
<table class="form-table">
  <?php $settings->generate_settings_html();?>
</table>
<?php
$merchant = false;
$api_key = $settings->get_api_key();
if (!empty($api_key)) {
    $merchant = get_transient('vindi_merchant');
}

?>

<div class="below-h2 <?php echo $merchant !== false ? 'updated' : 'error'; ?>">
	<h3 class="wc-settings-sub-title title-1">
		<?php _e('Link de configuração dos Eventos da Vindi', VINDI);?>
	</h3>

	<p><?php _e('Copie esse link e utilize-o para configurar os eventos nos Webhooks da Vindi.', VINDI);?></p>

  <input type="text" value="<?php echo $settings->get_webhooks_url(); ?>" readonly="readonly"
    onClick="this.select(); this.setSelectionRange(0, this.value.length); document.execCommand('copy');"/>
  <hr>
	<div class="test-return-infos">
    <?php if ($merchant): ?>
        <div>
          <h3 class="wc-settings-sub-title title-2"><?php _e('Teste de conexão com a Vindi', VINDI);?></h3>
          <p><?php _e('Conectado com sucesso!', VINDI)?></p>
        </div>
        <div>
          <p><?php echo sprintf(__('Conta: <strong>%s</strong>', VINDI), $merchant['name']) ?></p>
        </div>
        <div>
          <p><?php echo sprintf(__('Status: <strong>%s</strong>', VINDI), ucwords($merchant['status'])) ?></p>
        </div>
    <?php else: ?>
        <div>
          <h3 class="wc-settings-sub-title title-2"><?php _e('Teste de conexão com a Vindi', VINDI);?></h3>
          <p><?php echo sprintf(__('Falha na conexão! <br><strong>%s</strong>', VINDI), $settings->api->last_error); ?></p>
        </div>
        <?php if (isset($api_key) && strlen($api_key) === 43 && $settings->api->last_error !== "unauthorized|authorization"): ?>
        <script type="text/javascript">
          jQuery(document).ready(function(){
                  jQuery('.wc-settings-sub-title').parent().append('<div class="alert alert-info">Aguarde! Reconectando ao Vindi.</div>');
                  setTimeout(function(){jQuery("button[name='save']").click();},2e3);
          });
			  </script>
		  <?php endif;?>
    <?php endif;?>
	</div>
</div>
