<?php

namespace VindiPaymentGateways;

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

  /**
   * Get a subscription that has an item equals as an order item, if any.
   *
   * @since 1.0.0
   * @param WC_Order $order A WC_Order object
   * @param WC_Order_Item_Product $order_item The order item
   *
   * @return WC_Subscription
   */
  public static function get_matching_subscription($order, $order_item)
  {
		$subscriptions = wcs_get_subscriptions_for_order($order, array('order_type' => 'parent'));
    $matching_subscription = null;
    foreach ($subscriptions as $subscription) {
      foreach ($subscription->get_items() as $subscription_item) {
        $line_item = wcs_find_matching_line_item($order, $subscription_item, $match_type = 'match_attributes');
        if($order_item === $line_item) {
          $matching_subscription = $subscription;
          break 2;
        }
      }
    }

		if (null === $matching_subscription && !empty($subscriptions)) {
			$matching_subscription = array_pop($subscriptions);
		}

		return $matching_subscription;
	}

  /**
   * Get the subscription item that matches the order item.
   *
   * @since 1.0.0
   * @param WC_Subscription $subscription The WC_Subscription object
   * @param WC_Order_Item_Product $order_item The order item
   * @param string $match_type Optional. The type of comparison to make. Can be 'match_product_ids' to compare product|variation IDs or 'match_attributes' to also compare by item attributes on top of matching product IDs. Default 'match_attributes'.
   *
   * @return WC_Order_Item_Product|bool
   */
  public static function get_matching_subscription_item($subscription, $order_item, $match_type = 'match_attributes')
  {
		$matching_item = false;

    if ('match_attributes' === $match_type) {
      $order_item_attributes = wp_list_pluck($order_item->get_formatted_meta_data('_', true), 'value', 'key');
    }

    $order_item_canonical_product_id = wcs_get_canonical_product_id($order_item);

    foreach ($subscription->get_items() as $subscription_item) {
      if (wcs_get_canonical_product_id($subscription_item) !== $order_item_canonical_product_id) {
        continue;
      }

      // Check if we have matching meta key and value pairs loosely - they can appear in any order,
      if ('match_attributes' === $match_type && wp_list_pluck($subscription_item->get_formatted_meta_data('_', true), 'value', 'key') != $order_item_attributes) {
        continue;
      }

      $matching_item = $subscription_item;
      break;
    }

		return $matching_item;
  }

  /**
   * Sanitize user input to prevent XSS atacks.
   *
   * @since 1.0.0
   * @param string $value. String to be sanitized.
   *
   * @return string
   */
  public static function sanitize_xss($value) {
    return htmlspecialchars(strip_tags($value));
  }

  /**
   * Sort arrays by keys maintains index association.
   * 
   * @since 1.0.1
   * @param array $arr. Array to order.
   * @param string $on. String key to filter.
   * @param defined $order. Order by ASC or DESC
   * 
   * @return array
   */
  public static function array_sort($array, $on, $order=SORT_ASC)
  {
      $new_array = array();
      $sortable_array = array();
  
      if (count($array) > 0) {
          foreach ($array as $k => $v) {
              if (is_array($v)) {
                  foreach ($v as $k2 => $v2) {
                      if ($k2 == $on) {
                          $sortable_array[$k] = $v2;
                      }
                  }
              } else {
                  $sortable_array[$k] = $v;
              }
          }
  
          switch ($order) {
              case SORT_ASC:
                  asort($sortable_array);
              break;
              case SORT_DESC:
                  arsort($sortable_array);
              break;
          }
  
          foreach ($sortable_array as $k => $v) {
              $new_array[$k] = $array[$k];
          }
      }
  
      return $new_array;
  }
}


