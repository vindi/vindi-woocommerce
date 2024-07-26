<?php if (in_array($order_status, ['pending', 'auto-draft'])) : ?>
    <a class="btnCopyPostLink" id="copyLinkPostEdit" href="<?php echo $link_payment ?>">
        <img style="width: 15px;" src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo-white.svg'; ?>" alt="Copy icon">
        <span><?php echo esc_html__('Copiar Link', 'vindi-payment-gateway'); ?></span>
    </a>
    <script>
        const copyLinkPostEdit = document.querySelector('#copyLinkPostEdit')
        console.log(copyLinkPostEdit)
        copyLinkPostEdit.addEventListener('click', function(e) {
            e.preventDefault();
            const link = e.currentTarget.href;
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
        });
    </script>
<?php endif; ?>