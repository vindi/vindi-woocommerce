<?php

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
    // echo '<pre>';
    // print_r(WC()->session->get('coco'));
    // echo '</pre>';
    if (is_checkout()): ?>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
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

  public function calculate_cost($cart) {
    $ext_cst_label_billing 	= 'OLHA A TAXA';
    $ext_cst_amount_type = 'fixed';
    $ext_cst_amount = 24.69;

    if (!$_POST || (is_admin() && !is_ajax())) {
      return;
    }

    if (isset($_POST['post_data'] ) ) {
      parse_str($_POST['post_data'], $post_data );
    } else {
      $post_data = $_POST;
    }

    if (isset($post_data['vindi_cc_installments']) && $post_data['vindi_cc_installments'] > 1) {
      global $woocommerce;
      $interest_rate = VindiCreditGateway::get_interest_rate();
      $installments = intval($post_data['vindi_cc_installments']);
      $tax_total = 0;
      $taxes = $cart->get_taxes(); 
      foreach($taxes as $tax) $tax_total += $tax;
      $cart_total = ($cart->get_cart_contents_total() + $cart->get_shipping_total() + $tax_total);
      $total_price = $installments * ceil(($cart_total / $installments * 100) * ((1 + ($interest_rate / 100)) ** ($installments - 1))) / 100;
      $interest_price = (float) $total_price - $cart_total;
      WC()->cart->add_fee(__('Juros', VINDI), $interest_price);
    }
  }
}
