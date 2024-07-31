<?php if (in_array($order_status, ['pending', 'auto-draft'])) : ?>
    <a class="btnCopyPostLink" id="copyLinkPostEdit" href="<?php echo $link_payment ?>" target="_blank">
        <img style="width: 15px;"
        src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>"
        alt="Copy icon">
        <span><?php echo esc_html__('Copiar Link', 'vindi-payment-gateway'); ?></span>
    </a>
<?php endif; ?>