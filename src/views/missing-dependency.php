<?php
/**
 * Admin View: Notice - Warning when the site doesn't have a critical dependency installed
 * or it doesn't have the minimum version
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="vindi-alert notice vindi-error d-flex" style=>
  <p>
    <?php echo sprintf(
      __('O Plugin Vindi WooCommerce depende da versão %s do %s para funcionar!', VINDI),
      $version,
      $name,
    )?>
  </p>
  <a class="btn-to-right" href="<?php echo $link; ?>">Instalar e Ativar</a>
</div>
