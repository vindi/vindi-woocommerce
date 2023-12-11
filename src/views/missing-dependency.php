<div class="vindi-alert notice vindi-error d-flex">
  <p>
    <?php echo sprintf(
        __('O Plugin Vindi WooCommerce depende da versão %s ou maior do plugin %s para funcionar!', VINDI),
      $version,
      $name,
    )?>
  </p>
  <a class="btn-to-right" href="<?php echo $link; ?>">Instalar e Ativar</a>
</div>
