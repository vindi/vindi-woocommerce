<?php if (in_array($status, ['pending', 'auto-draft'])) : ?>
    <div style="display: flex;gap: 6px; width: 100%;">
        <div style="display: flex;gap: 6px; width: 100%;">
            <?php $is_disabled = ($has_item) ? 'enable' : 'disabled'; ?>
            <a class="buttonPaymentLink <?php echo $is_disabled; ?>"
            target="<?php echo $has_item ? esc_attr('_blank') : ''; ?>"
            href="<?php echo $has_item ? esc_url($link_payment) : '#'; ?>">
                <img style="width: 15px;"
                src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>"
                alt="Logo Vindi">
                <span><?php echo esc_html__('Link de pagamento', 'vindi-payment-gateway'); ?></span>
            </a>
            <a class="buttonCopy" id="buttonCopyPost">
                <img style="width: 15px;" 
                src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/copy.svg'; ?>" 
                alt="Icone Copiar">
            </a>
        </div>
    </div>
    <div>
        <?php if (!$has_item) : ?>
            <span class="notificationPaymentLink">
                <?php
                echo esc_html__(
                    'O pedido tem que ter pelo menos um item',
                    'vindi-payment-gateway'
                );
                ?>
            </span>
        <?php endif; ?>
        <?php if (!$has_sub && $has_item) : ?>
            <span class="notificationPaymentLink">
                <?php
                echo esc_html__(
                    'O pedido possui uma assinatura, por favor para criar pedidos com assinaturas acessar o link:',
                    'vindi-payment-gateway'
                );
                ?>
                <a href="<?php echo $urlShopSubscription; ?>" target="_blank">Assinaturas</a>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>