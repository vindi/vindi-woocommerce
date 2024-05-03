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
                    <?php _e('Aqui estão as sua cobranças PIX.', VINDI); ?>
                </p>
                <p class="message_description">
                    <?php
                        _e(
                            'Você pode pagar lendo o QR Code abaixo ou copiando a linha digitável. 
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
                        <div class="pix_charge charge">
                            <span class="pix_product_title product_title">
                                <?php echo $subscription['product']; ?>
                            </span>
                            <div>
                                <?php
                                    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
                                    $pix_expiration = new DateTime(
                                        $subscription['bill']['pix_expiration'],
                                        new DateTimeZone('America/Sao_Paulo')
                                    );
                                if ($pix_expiration < $now && $key !== 'single_payment') :?>
                                    <div style="display: flex;
                                                flex-direction: column;
                                                align-items: center;
                                                justify-content: end;position: relative;
                                                right: 75px;
                                                font-size: 14px"
                                    >
                                        <div>
                                            <span>Seu Qr Code Expirou!</span>
                                        </div>
                                        <a id="generate_new_qr_code"
                                            data-order="<?php echo esc_attr($order_id)?>"
                                            data-charge="<?php echo esc_attr($subscription['bill']['charge_id'])?>"
                                            data-subscription="<?php echo esc_attr($key)?>"
                                            class="download_button">
                                            <?php _e('Renovar QR Code', VINDI); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" 
                                                     viewBox="0 0 512 512"
                                                >
                                                    <path d="M463.5 224H472c13.3 0 24-10.7 
                                                             24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 
                                                             5.2L413.4 
                                                             96.6c-87.6-86.5-228.7-86.2-315.8 
                                                             1c-87.5 87.5-87.5 229.3 0 
                                                             316.8s229.3 87.5 316.8 0c12.5-12.5 
                                                             12.5-32.8 0-45.3s-32.8-12.5-45.3 
                                                             0c-62.5 62.5-163.8 62.5-226.3 
                                                             0s-62.5-163.8 0-226.3c62.2-62.2 
                                                             162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 
                                                             26.2s12.5 14.8 22.2 14.8H463.5z"
                                                    />
                                                </svg>
                                        </a>
                                    </div>
                                <?php else : ?>
                                    <div class="qr_code_viwer">
                                        <object type="image/svg+xml"
                                                alt="QR Code image"
                                                data="<?php echo esc_url($subscription['bill']['pix_qr']); ?>">
                                        </object>
                                    </div>
                                    <div style="display: flex;
                                                align-items: center;
                                                justify-content: end;
                                                position: relative;
                                                right: 75px;">
                                        <a id="copy_vindi_pix_code"
                                            class="download_button copy_vindi_line"
                                            data-code="<?php echo esc_attr($subscription['bill']['pix_code']); ?>">
                                                <?php _e('Copiar código', VINDI); ?>
                                                <svg color="#006DFF"
                                                     style="padding: 0 5px;"
                                                     xmlns="http://www.w3.org/2000/svg"
                                                     viewBox="0 0 448 512">
                                                    <path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 
                                                             7.2-16 16-16l140.1 
                                                             0L400 115.9V320c0 8.8-7.2 16-16 16zM192 
                                                             384H384c35.3 0 64-28.7 
                                                             64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 
                                                             14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 
                                                             0-64 28.7-64 64V320c0 35.3 28.7 64 
                                                             64 64zM64 128c-35.3 0-64 
                                                             28.7-64 64V448c0 35.3 28.7 64 64 
                                                             64H256c35.3 0 64-28.7 
                                                             64-64V416H272v32c0 8.8-7.2 16-16 
                                                             16H64c-8.8 0-16-7.2-16-16V192c0-8.8 
                                                             7.2-16 16-16H96V128H64z"
                                                    />
                                                </svg>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endif; ?>
    <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
