<?php
if (!defined('ABSPATH')) {
  exit;
}

include_once VINDI_PATH . 'src/helpers/VindiHelpers.php';

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 1.0.0
 */

abstract class VindiPaymentGateway extends WC_Payment_Gateway_CC
{
  /**
   * Create the level 3 data array to send to Vindi when making a purchase.
   *
   * @param WC_Order $order The order that is being paid for.
   * @return array          The level 3 data to send to Vindi.
   */
  public function get_level3_data_from_order($order)
  {
    // WC Versions before 3.0 don't support postcodes and are
    // incompatible with level3 data.
    if (VindiHelpers::is_wc_lt('3.0')) {
      return array();
    }

    // Get the order items. Don't need their keys, only their values.
    // Order item IDs are used as keys in the original order items array.
    $order_items = array_values($order->get_items());
    $currency    = $order->get_currency();

    $vindi_line_items = array_map(function ($item) use ($currency) {
      $product_id          = $item->get_variation_id()
        ? $item->get_variation_id()
        : $item->get_product_id();
      $product_description = substr($item->get_name(), 0, 26);
      $quantity            = $item->get_quantity();
      $unit_cost           = VindiHelpers::get_vindi_amount(($item->get_subtotal() / $quantity), $currency);
      $tax_amount          = VindiHelpers::get_vindi_amount($item->get_total_tax(), $currency);
      $discount_amount     = VindiHelpers::get_vindi_amount($item->get_subtotal() - $item->get_total(), $currency);

      return (object) array(
        'product_code'        => (string) $product_id, // Up to 12 characters that uniquely identify the product.
        'product_description' => $product_description, // Up to 26 characters long describing the product.
        'unit_cost'           => $unit_cost, // Cost of the product, in cents, as a non-negative integer.
        'quantity'            => $quantity, // The number of items of this type sold, as a non-negative integer.
        'tax_amount'          => $tax_amount, // The amount of tax this item had added to it, in cents, as a non-negative integer.
        'discount_amount'     => $discount_amount, // The amount an item was discounted—if there was a sale,for example, as a non-negative integer.
      );
    }, $order_items);

    $level3_data = array(
      'merchant_reference'   => $order->get_id(), // An alphanumeric string of up to  characters in length. This unique value is assigned by the merchant to identify the order. Also known as an “Order ID”.

      'shipping_amount'      => VindiHelpers::get_vindi_amount($order->get_shipping_total() + $order->get_shipping_tax(), $currency), // The shipping cost, in cents, as a non-negative integer.
      'line_items'           => $vindi_line_items,
    );

    // The customer’s U.S. shipping ZIP code.
    $shipping_address_zip = $order->get_shipping_postcode();
    if ($this->is_valid_us_zip_code($shipping_address_zip)) {
      $level3_data['shipping_address_zip'] = $shipping_address_zip;
    }

    // The merchant’s U.S. shipping ZIP code.
    $store_postcode = get_option('woocommerce_store_postcode');
    if ($this->is_valid_us_zip_code($store_postcode)) {
      $level3_data['shipping_from_zip'] = $store_postcode;
    }

    return $level3_data;
  }
};
