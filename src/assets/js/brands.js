class Brands {
  constructor() {
    if (!document.querySelector(".wc_payment_method .payment_method_vindi-credit-card")) return;

    this.setEvents();
  }

  setEvents() {
    const card = document.querySelector("#vindi_cc_cardnumber");
    let brand = this.getCard(card.value.replace(/\s/g, ""));
    
    this.setBrand(brand);
    this.setCardColor(card);

    if (card) {
      card.addEventListener("keyup", () => {
        brand = this.getCard(card.value.replace(/\s/g, ""));
        
        this.setBrand(brand);
        this.setCardColor(card);
      });
    }
  }

  setCardColor(card) {
    const brand = this.getCard(card.value.replace(/\s/g, ""));
    switch (brand) {
      case 'elo': this.swapColor('black'); break;
      case 'hipercard': this.swapColor('reddark'); break;
      case 'diners_club': this.swapColor('blue'); break;
      case 'american_express': this.swapColor('greendark'); break;
      case 'mastercard': this.swapColor('purpledark'); break;
      case 'visa': this.swapColor('bluedark'); break;
      case 'discover': this.swapColor('grey'); break;
      case 'jcb': this.swapColor('orangedark'); break;
      default: this.swapColor('greydark'); break;
    }
  }

  setBrand(brand) {
    const img = document.querySelector("#vindi_cc_ccicon");
    const single = document.querySelector("#vindi_cc_ccsingle > img");
    const hidden = document.querySelector('input[name="vindi_cc_paymentcompany"]');
    
    const icons = [ img, single ];
        
    icons.forEach(icon => {
      if (icon) {
        const attr = icon.getAttribute("data-img");

        if (attr) {
          let image = icon === single ? `single/${brand}` : brand;

          icon.src = icon.src.replace(attr, image);
          icon.setAttribute("data-img", image);
        }
      }
    });

    if (hidden) hidden.value = brand;
  }

  getCard(card) {
    const brandsRegex = {

      mastercard: new RegExp("^5[1-5][0-9]{14}$"),
      visa: new RegExp("^4[0-9]{12}(?:[0-9]{3})?$"),
      elo: new RegExp(
        "^(4011(78|79)|43(1274|8935)|45(1416|7393|763(1|2))|50(4175|6699|67[0-7][0-9]|9000)|50(9[0-9][0-9][0-9])|627780|63(6297|6368)|650(03([^4])|04([0-9])|05(0|1)|05([7-9])|06([0-9])|07([0-9])|08([0-9])|4([0-3][0-9]|8[5-9]|9[0-9])|5([0-9][0-9]|3[0-8])|9([0-6][0-9]|7[0-8])|7([0-2][0-9])|541|700|720|727|901)|65165([2-9])|6516([6-7][0-9])|65500([0-9])|6550([0-5][0-9])|655021|65505([6-7])|6516([8-9][0-9])|65170([0-4]))\\d{0,15}"
      ),
      hipercard: new RegExp("^606282|^3841(?:[0|4|6]{1})0"),
      jcb: new RegExp("^(?:2131|1800|35[0-9]{3})[0-9]{11}$"),
      diners_club: new RegExp("^3(?:0[0-5]|[68][0-9])[0-9]{11}$"),
      discover: new RegExp("^6(?:011|5[0-9]{2})[0-9]{12}$"),
      american_express: new RegExp("^3[47][0-9]{13}$"),
      aura: new RegExp("^((?!504175))^((?!5067))(^50[0-9])"),
    };

    for (let brand in brandsRegex) {
      if (brandsRegex[brand].test(card)) {
        return brand;
      }
    }

    return "unknown";
  }

  swapColor (basecolor) {
    const color = document.querySelectorAll('.vindi_cc_cardcolor');

    if (color) {
      color.forEach(function (input) {
        input.setAttribute('class', '');
        input.setAttribute('class', 'vindi_cc_cardcolor ' + basecolor);
      });
    }
  };
}

new Brands;

jQuery('body').on('updated_checkout', () => { 
  new Brands;
})

  