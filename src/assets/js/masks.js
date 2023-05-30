class Masks {
  constructor() {
    if (!document.querySelector(".wc_payment_method .payment_method_vindi-credit-card")) return;

    this.handleMask();
  }

  handleMask() {
    const ids = [
      "#vindi_cc_cardnumber",
      "#vindi_cc_expirationdate",
      "#vindi_cc_securitycode",
      "#vindi_cc_name"
    ];

    ids.forEach(id => {
      const mask  = this.getMask(id);
      const field = document.querySelector(id);

      if (Object.keys(mask).length > 0 && field) {
        IMask(field, mask);
      }
    });
  }

  getMask(id) {
    let selected;
    switch (id) {
      case "#vindi_cc_cardnumber":
          selected = { mask: '0000 0000 0000 0000' }
        break;
      case "#vindi_cc_expirationdate":
          selected = { mask: '00/00' };
        break;
      case "#vindi_cc_securitycode":
          selected = { mask: '0000' };
        break;
      case "#vindi_cc_name":
          selected = { mask: /^[A-Za-z\s]*$/ };
        break;
      default:
          selected = {};
        break;
    }
    return selected;
  }
}

new Masks;

jQuery('body').on('updated_checkout', () => {
  new Masks;
});
