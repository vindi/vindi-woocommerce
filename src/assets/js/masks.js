class Masks {
  constructor() {
    if (!document.querySelector(".wc_payment_method .payment_method_vindi-credit-card")) return;

    this.setEvents();
  }

  setEvents() {
    this.cardMask();
    this.dateMask();
    this.cvvMask();
    this.ownerMask();
  }

  cardMask() {
    const card = document.querySelector("#vindi_cc_cardnumber");
    
    if (card) {
      var mask = {
        mask: '0000 0000 0000 0000'
      };
      IMask(card, mask);
    }
  }

  dateMask() {
    const date = document.querySelector("#vindi_cc_expirationdate");

    if (date) {
      var mask = {
        mask: '00/00'
      };
      IMask(date, mask);
    }
    
  }

  cvvMask() {
    const cod = document.querySelector("#vindi_cc_securitycode");

    if (cod) {
      var mask = {
        mask: '0000'
      };
      IMask(cod, mask);
    }
  }

  ownerMask() {
    const cod = document.querySelector("#vindi_cc_name");

    if (cod) {
      var mask = {
        mask: /^[A-Za-z\s]*$/
      };
      IMask(cod, mask);
    }
  }
}

new Masks;

jQuery('body').on('updated_checkout', () => {
  new Masks;
});
