/* global wcbcf_public_params */
(function ($) {
  "use strict";
  $(function () {
    $("#vindi_ccNo").payment("formatCardNumber");
    $("#vindi_expdate").payment("formatCardExpiry");
    $("#vindi_cvv").payment("formatCardCVC");
    $("body").on("updated_checkout", function () {
      $("#vindi_ccNo").payment("formatCardNumber");
      $("#vindi_expdate").payment("formatCardExpiry");
      $("#vindi_cvv").payment("formatCardCVC");
    });
  });
})(jQuery);
