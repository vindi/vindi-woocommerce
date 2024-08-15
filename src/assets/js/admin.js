jQuery(document).ready(function ($) {
  'use strict';

  var cycles_input = document.querySelector('#cycle_count'),
    $cycles_input = $(cycles_input);
  var cycles_field = document.querySelector('.cycle_count_field'),
    $cycles_field = $(cycles_field);
  var interest_rate_input = document.querySelector('#woocommerce_vindi-credit-card_interest_rate'),
    $interest_rate_input = $(interest_rate_input);
  var wcs_number_payments = document.querySelector('#wcs_number_payments'),
    $wcs_number_payments = $(wcs_number_payments);

  $interest_rate_input.mask('##0.00%', {
    reverse: true,
    onKeyPress: function (val, e, field, options) {
      var old_value = $(field).data('oldValue') || '';

      val = val.trim();
      val = val.replace('%', '');
      val = val.replace(',', '.');
      val = val.length > 0 ? val : '0.01';

      val = val.replace(/[\.]+/, '.');

      var dot_occurrences = (val.match(/\./g) || []).length > 1;

      var is_float = /[+]?[\d]*\.?[\d]+/.test(val);

      if (dot_occurrences || !is_float) {
        val = old_value;
      }
      val = parseFloat(val);

      $(field).val(`${val}%`).data('oldValue', val);
    }
  });

  if ($wcs_number_payments) {
    $wcs_number_payments.on('change', function () {
      var val = $(this).val();
      var regex = new RegExp(/^(1|2|3|4|5|6|7|8|9|10|11|12)$/);
      if (val != '' && !regex.test(val)) {
        $(this).val('');
      }
    });
  }

  /**
   * Subscription coupon actions.
   * @type {{init: function, type_options: function, move_field: function}}
   */
  var vindi_meta_boxes_coupon_actions = {

    /**
     * Initialize variation actions.
     */
    init: function () {
      if (cycles_field) {
        $(document.getElementById('discount_type')).on('change', this.type_options).change();
        this.move_field();
      }
    },

    /**
     * Show/hide fields by coupon type options.
     */
    type_options: function () {
      var discount_type = $(this).val();

      switch (discount_type) {
        case 'recurring_percent':
          if ($cycles_field.is(':visible')) {
            $cycles_field.hide();
            $cycles_input.val('0');
          }
          break;
        case 'fixed_cart':
          if ($cycles_field.is(':visible')) {
            $cycles_field.hide();
            $cycles_input.val('1');
          }
          break;
        default:
          if ($cycles_field.is(':hidden')) {
            $cycles_field.show();
            $cycles_input.val('0');
          }
          break;
      }
    },

    /**
     * Move the renewal form field in the DOM to a better location.
     */
    move_field: function () {
      var parent = document.getElementById('general_coupon_data'),
        shipping = parent.querySelector('.free_shipping_field');

      parent.insertBefore(cycles_field, shipping);
    }
  };

  vindi_meta_boxes_coupon_actions.init();

  const btnPaymentLink = document.querySelector('.buttonPaymentLink');
  if (btnPaymentLink.classList.contains('disabled')) {
    btnPaymentLink.addEventListener('click', function (e) {
      e.preventDefault();
    });
  }

  const btnCopyPost = document.querySelector('#buttonCopyPost');
  btnCopyPost.addEventListener("click", function (e) {
    e.preventDefault();
    let paymentLink = document.querySelector('.buttonPaymentLink.enable');
    if (paymentLink) {
      let hrefValue = paymentLink.getAttribute('href');
      let tempInput = document.createElement('input');
      tempInput.value = hrefValue;
      document.body.appendChild(tempInput);
      tempInput.select();
      tempInput.setSelectionRange(0, 99999);
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      console.log('Link copiado.');
    } else {
      console.log('Link de pagamento não encontrado ou está desabilitado.');
    }
  });

  const url = new URLSearchParams(location.href);
  if (url.has("vindi-payment-link")) {
    const addresses = document.querySelectorAll(".edit_address");

    addresses.forEach((address) => {
      address.style.display = "block";
    });

    if (url.has("gateway")) {
      this.updateLinkGateway(url.get("gateway"));
    }
  }
});