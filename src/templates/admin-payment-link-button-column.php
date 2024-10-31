<?php if (in_array($status, ['pending', 'auto-draft'])) : ?>
    <?php $is_disabled = ($post_type == 'admin' && $has_sub && $has_item) ? false : true; ?>
    <?php if ($is_disabled) : ?>
        <a class="btnCopyPostLink" id="copyLinkPostEdit"
        href="<?php echo $url_payment ?>"
        target="_blank">
            <img style="width: 15px;"
            src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>"
            alt="Logo Vindi">
            <span><?php echo esc_html__('Link de Pagamento', 'vindi-payment-gateway'); ?></span>
        </a>
    <?php endif; ?>
<?php endif; ?>