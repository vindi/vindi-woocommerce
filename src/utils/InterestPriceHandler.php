<?php

namespace VindiPaymentGateways;

if (! defined('ABSPATH')) {
  die();
	exit; // Exit if accessed directly
}

class InterestPriceHandler {

  function __construct() {
    add_action('wp_footer', array($this, 'add_installment_change_script'));
    add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_cost'));
  }

  public function add_installment_change_script() {
    if (is_checkout()): ?>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
          $('form.checkout').on('change', 'input[name="payment_method"]', function() {
            $('body').trigger('update_checkout');
          });
          $('form.checkout').on('change', 'select[name^="vindi_cc_installments"]', function() {
            const selectedValue = $(this).val();
            $('body').trigger('update_checkout');

            // Keep the instalment value selected
            $(`select[name^="vindi_cc_installments"] option[value="${selectedValue}"]`).prop('selected', true);
            $('body').on('updated_checkout', function(){
              $(`select[name^="vindi_cc_installments"] option[value="${selectedValue}"]`).prop('selected', true);
            });
          });

        });
      </script>
    <?php endif;
  }

  /**
   * @SuppressWarnings(PHPMD.Superglobals)
   */
  public function calculate_cost($cart) {
    global $woocommerce;
        if (is_admin() && !is_ajax()) {
          return;
        }
    if (isset($_POST['post_data'] ) ) {
      parse_str(sanitize_text_field($_POST['post_data']), $post_data);
    } else {
      $post_data = $_POST;
    }
    if (isset($post_data['vindi_cc_installments']) &&
        filter_var($post_data['vindi_cc_installments'], FILTER_SANITIZE_NUMBER_INT) > 1 &&
        $post_data['payment_method'] === 'vindi-credit-card' &&
        get_option('woocommerce_vindi-credit-card_settings', true)['enable_interest_rate'] === 'yes'
    ) {
            $this->add_order_fee($post_data, $cart);
    }
  }

    private function add_order_fee($post_data, $cart)
    {
      $interest_rate = get_option('woocommerce_vindi-credit-card_settings', true)['interest_rate'];
      $installments  = intval(filter_var($post_data['vindi_cc_installments'], FILTER_SANITIZE_NUMBER_INT));
      $tax_total     = 0;
      $taxes         = $cart->get_taxes();
        foreach ($taxes as $tax) {
            $tax_total += $tax;
        }
      $cart_total     = ($cart->get_cart_contents_total() + $cart->get_shipping_total() + $tax_total);
      $total_price    = $cart_total * (1 + (($interest_rate / 100) * ($installments - 1)));
      $interest_price = (float) $total_price - $cart_total;

      WC()->cart->add_fee(__('Juros', VINDI), $interest_price);
    }
}
