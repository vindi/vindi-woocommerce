<?php
class VindiHelpers
{


  function __construct()
  {

    add_action('woocommerce_process_product_meta', array($this, 'wc_post_meta'));
  }

  /**
   * Sanitize statement descriptor text.
   *
   * Vindi requires max of 22 characters and no
   * special characters with ><"'.
   *
   * @since 1.0.0
   * @param string $statement_descriptor
   * @return string $statement_descriptor Sanitized statement descriptor
   */
  public static function clean_statement_descriptor($statement_descriptor = '')
  {
    $disallowed_characters = array('<', '>', '"', "'");

    // Remove special characters.
    $statement_descriptor = str_replace($disallowed_characters, '', $statement_descriptor);

    $statement_descriptor = substr(trim($statement_descriptor), 0, 22);

    return $statement_descriptor;
  }

  /**
   * Get Vindi amount to pay
   *
   * @param float  $total Amount due.
   * @param string $currency Accepted currency.
   *
   * @return float|int
   */

  public static function get_vindi_amount($total, $currency = '')
  {
    if (!$currency) {
      $currency = get_woocommerce_currency();
    }

    return absint(wc_format_decimal(((float) $total * 100), wc_get_price_decimals())); // In cents.

  }

  /**
   * Checks if WC version is less than passed in version.
   *
   * @since 1.0.0
   * @param string $version Version to check against.
   * @return bool
   */
  public static function is_wc_lt($version)
  {
    return version_compare(WC_VERSION, $version, '<');
  }

  /**
   * Save Woocommerce custom attributes
   *
   * @since 1.0.0
   * @param string $version Version to check against.
   * @return null
   */

  public static function wc_post_meta($post_id, $custom_attributes)
  {

    // Get product
    $product = wc_get_product($post_id);

    $i = 0;

    // Loop through the attributes array
    foreach ($custom_attributes as $name => $value) {

      // Check meta value exists
      $product->update_meta_data($name, $value);

      $i++;
    }

    $product->save();
  }
};
