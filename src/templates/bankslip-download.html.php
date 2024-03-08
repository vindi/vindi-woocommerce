<?php if (!defined('ABSPATH')) exit; ?>
<?php if (isset($vindi_order)): ?>
    <div class="vindi_payment_listing">
		<div class="info_message">
			<div class="icon"></div>
			<div class="message">
				<p class="message_title">
					<?php _e('Aqui estão os boletos de suas compras.', VINDI); ?>
				</p>
				<p class="message_description">
					<?php _e('Você pode imprimi-los e pagá-los via internet banking ou em agências bancárias e lotéricas. Após recebermos a confirmação do pagamento, seu pedido será processado.', VINDI); ?>
				</p>
			</div>
		</div>
        <div class="charges">
    <?php foreach ($order_to_iterate as $subscription) : ?>
				<?php if (is_array($subscription) && array_key_exists('product', $subscription) && !in_array($subscription['bill']['status'], array('paid', 'canceled'))): ?>
                        <div class="charge">
							<span class="product_title">
								<?php echo $subscription['product']; ?>
							</span>
							<a class="download_button" href="<?php echo esc_url($subscription['bill']['bank_slip_url']); ?>" target="_blank">
								<?php _e('Baixar boleto', VINDI ); ?>
							</a>
						</div>
				<?php else: ?>
					<?php foreach ($subscription as $item): ?>
						<?php if (is_array($item) && array_key_exists('product', $item) && !in_array($item['bill']['status'], array('paid', 'canceled'))): ?>
                            <div class="charge">
								<span class="product_title">
									<?php echo $item['product']; ?>
								</span>
								<a class="download_button" href="<?php echo esc_url($item['bill']['bank_slip_url']); ?>" target="_blank">
									<?php _e('Baixar boleto', VINDI ); ?>
								</a>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
    <?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
