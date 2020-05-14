<?php if (!defined('ABSPATH')) exit; ?>
<?php if (isset($vindi_order)): ?>
	<div class="woocommerce-message">
		<span>
			<?php _e('Aqui estão os boletos da sua compra. Você imprimi-los e pagá-los via internet banking ou em agências bancárias e lotéricas.', VINDI); ?>
			<br/>
			<?php _e('Após recebermos a confirmação do pagamento, seu pedido será processado.', VINDI); ?>
		</span>
		<br><br>
		<?php foreach ($vindi_order as $item): ?>
			<?php if ($item['bill']['status'] != 'paid'): ?>
				<span>
					<a class="button" href="<?php echo esc_url($item['bill']['bank_slip_url']); ?>" target="_blank">
						<?php _e('Baixar boleto', VINDI ); ?>
					</a>
					<?php echo sprintf(__('Clique no botão ao lado para baixar o boleto de: %s.', VINDI), $item['product']); ?>
				</span>
				<br/>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>