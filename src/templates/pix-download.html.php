<?php if (!defined('ABSPATH')) exit; ?>
<?php if (isset($vindi_order)): ?>
	<div class="vindi_payment_listing">
		<div class="info_message">
			<div class="icon"></div>
			<div class="message">
				<p class="message_title">
					<?php _e('Aqui estão as sua cobranças PIX.', VINDI); ?>
				</p>
				<p class="message_description">
					<?php _e('Você pode pagar lendo o QR Code abaixo ou copiando a linha digitável. Após recebermos a confirmação do pagamento, seu pedido será processado.', VINDI); ?>
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
							<div>
								<div class="qr_code_viwer">
									<object type="image/svg+xml" alt="QR Code image" data="<?php echo esc_url($subscription['bill']['pix_qr']); ?>"></object>
								</div>
								<div style="display: flex;align-items: center;justify-content: end;position: relative;right: 75px;">
									<a href="#"
									   id="copy_vindi_pix_code"
									   class="download_button"
									   data-code="<?php echo esc_attr($subscription['bill']['pix_code']); ?>">
												<?php _e('Copiar código', VINDI); ?>
												<svg color="#006DFF" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z"/></svg>
									</a>
								</div>
							</div>
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
