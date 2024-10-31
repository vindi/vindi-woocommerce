<?php if (in_array($status, ['pending', 'auto-draft'])) : ?>
    <?php if ($type == 'shop_order') : ?>
        <div style="display:flex;gap:6px;width:100%;margin-top:6px;padding-top:10px;">
            <?php
            $is_disabled = ($disable) ? 'enable' : 'disabled';
            ?>
            <a class="buttonPaymentLink <?php echo $is_disabled; ?>"
                target="<?php echo $item ? esc_attr('_blank') : ''; ?>"
                href="<?php echo $is_disabled == 'enable' ? esc_url($link) : '#'; ?>">
                <img style="width: 15px;"
                    src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>"
                    alt="Logo Vindi">
                <span><?php echo esc_html__('Ver link de pagamento', 'vindi-payment-gateway'); ?></span>
            </a>
            <a class="buttonCopy" id="buttonCopyPost">
                <img style="width: 15px;"
                    src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/copy.svg'; ?>"
                    alt="Icone Copiar">
            </a>
        </div>
        <div>
            <?php if (!$item) : ?>
                <span class="notificationPaymentLink">
                    <?php
                    echo esc_html__(
                        'O pedido tem que ter pelo menos um item',
                        'vindi-payment-gateway'
                    );
                    ?>
                </span>
            <?php endif; ?>
            <?php if (!$hasClient) : ?>
                <span class="notificationPaymentLink">
                    <?php
                    echo esc_html__(
                        'Por favor adicione um cliente ao pedido.',
                        'vindi-payment-gateway'
                    );
                    ?>
                    <br />
                </span>
            <?php endif; ?>
            <?php if ($created == 'admin' && $sub && $item) : ?>
                <span class="notificationPaymentLink">
                    <?php
                    echo esc_html__(
                        'O pedido possui uma assinatura, por favor para criar pedidos com assinaturas acessar o link:',
                        'vindi-payment-gateway'
                    );
                    ?>
                    <a href="<?php echo $shop; ?>" target="_blank">Assinaturas</a>
                    <br />
                </span>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div style="display: flex;gap: 6px; width: 100%;margin-top: 6px;">
            <div style="display: flex;gap: 6px; width: 100%;">
                <?php $is_disabled = ($disable) ? 'enable' : 'disabled'; ?>
                <a class="buttonPaymentLink <?php echo $is_disabled; ?>"
                    target="<?php echo $item ? esc_attr('_blank') : ''; ?>"
                    href="<?php echo $is_disabled == 'enable' ? esc_url($link) : '#'; ?>">
                    <img style="width: 15px;"
                        src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>"
                        alt="Logo Vindi">
                    <span><?php echo esc_html__('Ver link de pagamento', 'vindi-payment-gateway'); ?></span>
                </a>
                <a class="buttonCopy" id="buttonCopyPost">
                    <img style="width: 15px;"
                        src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/copy.svg'; ?>"
                        alt="Icone Copiar">
                </a>
            </div>
        </div>
        <div>
            <?php if (!$item) : ?>
                <span class="notificationPaymentLink">
                    <?php
                    echo esc_html__(
                        'O pedido tem que ter pelo menos um item',
                        'vindi-payment-gateway'
                    );
                    ?>
                </span>
            <?php endif; ?>
            <?php if ($single && $sub) : ?>
                <span class="notificationPaymentLink">
                    <?php
                    echo esc_html__(
                        'O pedido tem que ter apenas assinaturas',
                        'vindi-payment-gateway'
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>