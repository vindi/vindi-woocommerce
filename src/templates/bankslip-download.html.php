<?php if (!defined('ABSPATH')) exit; ?>
<?php if (isset($vindi_order)): ?>
	<div class="vindi_bankslip_listing">
		<div class="info_message">
			<div class="icon"></div>
			<div class="message">
				<p class="message_title">
					<?php _e('Aqui estão os boletos de suas compras.', VINDI); ?>
				</p>
				<p class="message_description">
					<?php _e('Você pode imprimi-los e pagá-los via internet banking ou em agências bancárias e loréricas. Após recebermos a confirmação do pagamento, seu pedido será processado.', VINDI); ?>
				</p>
			</div>
		</div>
		<div class="bankslips">
			<?php foreach ($vindi_order as $item): ?>
				<?php if (!in_array($item['bill']['status'], array('paid', 'canceled'))): ?>
					<div class="bankslip">
						<span class="product_title">
							<?php echo $item['product']; ?>
						</span>
						<a class="download_button" href="<?php echo esc_url($item['bill']['bank_slip_url']); ?>" target="_blank">
							<?php _e('Baixar boleto', VINDI ); ?>
						</a>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
