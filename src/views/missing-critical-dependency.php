<?php
/**
 * Admin View: Notice - Warning when the site doesn't have a dependency installed
 * or it doesn't have the minimum version
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="vindi-alert notice vindi-error">
  <p>
    <?php echo sprintf(
      __('O Plugin Vindi WooCommerce depende da versão %s+ do %s para funcionar! Como a versão atual do %s é mais antiga, o plugin foi DESATIVADO!', VINDI),
      $version,
      $name,
      $name
    )?>
  </p>
</div>

