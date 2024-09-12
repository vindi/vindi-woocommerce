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
                    <?php _e('Aqui estão as sua cobranças com Bolepix.', VINDI); ?>
                </p>
                <p class="message_description">
                    <?php
                        _e(
                            'Você pode pagar utilizando PIX ou Boleto Bancário. 
                            Após recebermos a confirmação do pagamento, seu pedido será processado.',
                            VINDI
                        );
                    ?>
                </p>
            </div>
        </div>
        <div class="charges">
    <?php foreach ($order_to_iterate as $key => $subscription) : ?>
                <?php if (is_array($subscription) && array_key_exists('product', $subscription)
                        && !in_array($subscription['bill']['status'], array('paid', 'canceled'))) : ?>
                        <div class="bolepix_charge charge">
                            <span class="bolepix_product_title product_title">
                                <?php echo $subscription['product']; ?>
                            </span>
                            <div>
                                <div class="qr_code_viwer">
                                    <object type="image/svg+xml"
                                            alt="QR Code image"
                                            data="<?php echo esc_url($subscription['bill']['pix_qr']); ?>">
                                    </object>
                                </div>
                                <div style="display: flex;
                                            flex-direction: column;
                                            align-items: end;
                                            justify-content: end;
                                            position: relative;
                                            right: 75px;">
                                    <a id="copy_vindi_bolepix_code"
                                       class="download_button copy_vindi_line"
                                       data-code="<?php echo esc_attr($subscription['bill']['pix_code']); ?>">
                                        <?php _e('Copiar código PIX', VINDI); ?>
                                    </a>
                                    <a class="download_button"
                                       href="<?php echo esc_url($subscription['bill']['bank_slip_url']); ?>"
                                       target="_blank">
                                        <?php _e('Baixar boleto', VINDI); ?>
                                        <svg color="#006DFF"
                                             style="padding: 0 5px;"
                                             xmlns="http://www.w3.org/2000/svg"
                                             viewBox="0 0 512 512">
                                             <path d="M288 32c0-17.7-14.3-32-32-32s-32 
                                             14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 
                                             0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 
                                             45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 
                                             0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 
                                             28.7 64 64 64H448c35.3 0 64-28.7 
                                             64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 
                                             45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 
                                             56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                <?php endif; ?>
    <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
