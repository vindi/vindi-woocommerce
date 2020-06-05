<?php
/**
 * Admin View: Notice - Warning when the site doesn't have a critical dependency installed
 * or it doesn't have the minimum version
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="error">
  <p>
    <?php echo sprintf(
      __('O Plugin Vindi WooCommerce depende da versão %s do %s para funcionar!', VINDI),
      $version,
      "<a href=\"{$link}\">" . __($name, VINDI) . '</a>'
    )?>
  </p>
</div>