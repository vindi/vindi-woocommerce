/* global wcbcf_public_params */
jQuery(function($) {
  const vindi_plugin = {
    init: function() {
      /* woocommerce-extra-checkout-fields-for-brazil input mask toggle value
      if (wcbcf_public_params.maskedinput === "yes")
       */
      vindi_plugin.maskCC();
    },
    maskCC: function() {
      $("#misha_ccNo").mask("0000 0000 0000 0000");
      $("#misha_expdate").mask("00/00");
      $("#misha_cvv").mask("000");
    },
    unmaskCC: function() {
      $("#misha_ccNo").unmask();
      $("#misha_expdate").unmask();
      $("#misha_cvv").unmask();
    }
  };

  vindi_plugin.init();
});
