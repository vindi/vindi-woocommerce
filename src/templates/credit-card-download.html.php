<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (isset($vindi_order)) : ?>
    <div class="vindi_payment_listing">
        <div class="info_message">
            <div class="icon"></div>
            <div class="message">
                <p class="message_title">
                    <?php _e('Aqui estão os links de suas compras.', VINDI); ?>
                </p>
                <p class="message_description">
                    <?php _e('Você pode acessar-los e pagá-los via sistema Vindi.
					Após recebermos a confirmação do pagamento, seu pedido será processado.', VINDI); ?>
                </p>
            </div>
        </div>
        <div class="charges">
            <?php foreach ($order_to_iterate as $sub) : ?>
                <?php if (isset($sub['product']) && !in_array($sub['bill']['status'], ['paid', 'canceled'])) : ?>
                    <div class="charge">
                        <span class="product_title">
                            <?php echo $sub['product']; ?>
                        </span>
                        <a class="download_button"
                        href="<?php echo esc_url($sub['bill']['vindi_url']); ?>"
                        target="_blank">
                            <?php _e('Acessar link', VINDI); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <?php foreach ($sub as $item) : ?>
                        <?php $status = in_array($item['bill']['status'], ['paid', 'canceled'])?>
                        <?php if (isset($item['product']) && !$status) : ?>
                            <div class="charge">
                                <span class="product_title">
                                    <?php echo $item['product']; ?>
                                </span>
                                <a class="download_button"
                                href="<?php echo esc_url($item['bill']['vindi_url']); ?>"
                                target="_blank">
                                    <?php _e('Acessar link', VINDI); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>