class FrontEnd {
  constructor() {
    if (!document.querySelector('.vindi_cc_form-container')) return;

    this.setEvents();
  }

  setEvents() {
    this.setFocusEvent();
    this.setChangeEvents();
    this.handleCardImage();
    this.handleChangeCard();
  }

  handleChangeCard() {
    jQuery(".vindi-change-card").bind('click', function () {
      jQuery('.vindi-old-cc-data').hide();
      jQuery('.vindi-old-cc-data').find('input').prop('disabled', true);

      jQuery('.vindi-new-cc-data').find('input, select').prop('disabled', false);
      jQuery('.vindi-new-cc-data').show();

      return false;
    });
  }

  setChangeEvents() {
    const name = document.querySelector('#vindi_cc_name');
    const card = document.querySelector("#vindi_cc_cardnumber");
    const cvv = document.querySelector("#vindi_cc_securitycode");
    const date = document.querySelector('#vindi_cc_expirationdate');

    this.setExpirationDate(date);
    this.setCardHolderName(name);
    this.setCardNumber(card, 'vindi_cc_svgnumber', '0123 4567 8910 1112');
    this.setCardNumber(cvv, 'vindi_cc_svgsecurity', '985');

    name.addEventListener('keyup', () => {
      this.setCardHolderName(name);
    });

    card.addEventListener('keyup', () => {
      this.setCardNumber(card, 'vindi_cc_svgnumber', '0123 4567 8910 1112');
    });

    cvv.addEventListener('keyup', () => {
      this.setCardNumber(cvv, 'vindi_cc_svgsecurity', '985');
    });

    date.addEventListener('keyup', () => {
      this.setExpirationDate(date);
    });
  }

  setCardNumber(element, id, number) {
    const svg = document.getElementById(id);

    if (svg) {
      svg.innerHTML = element.value === '' ? number : element.value
    }
  }

  setCardHolderName(name) {
    const frontName = document.getElementById('vindi_cc_svgname');
    const backName = document.getElementById('vindi_cc_svgnameback');

    name.value = name.value.toUpperCase();

    if(!frontName || !backName) {
      return;
    }

    frontName.innerHTML = name.value === '' ? 'João da Silva' : name.value;
    backName.innerHTML = name.value === '' ? 'João da Silva' : name.value;
  }

  setExpirationDate(date) {
    const month = document.querySelector('input[name="vindi_cc_monthexpiry"]');
    const year  = document.querySelector('input[name="vindi_cc_yearexpiry"]');

    if (date.value.length == 0) {
      document.getElementById('vindi_cc_svgexpire').innerHTML = '12/25';
      month.value = '';
      year.value  = '';

    } else {
      let expiry_date = date.value;
      document.getElementById('vindi_cc_svgexpire').innerHTML = expiry_date;

      if (expiry_date.length === 5) {
        month.value = expiry_date.split('/')[0];
        year.value = `20${expiry_date.split('/')[1]}`;

      } else {
        month.value = '';
        year.value = '';
      }
    }
  }

  handleCardImage() {
    const preload = document.querySelector('.vindi_cc_preload');
    const card = document.querySelector('.vindi_cc_creditcard');

    if(preload) {
      preload.classList.remove('vindi_cc_preload');
    }

    if (card) {
      card.addEventListener('click', () => {
        card.classList.toggle('flipped');
      });
    }
  }

  setFocusEvent() {
    const name = document.getElementById('vindi_cc_name');
    const cardnumber = document.getElementById('vindi_cc_cardnumber');
    const expirationdate = document.getElementById('vindi_cc_expirationdate');
    const securitycode = document.getElementById('vindi_cc_securitycode');
    const card = document.querySelector('.vindi_cc_creditcard');
    const removeFocusElements = [ name, cardnumber, expirationdate, securitycode ];
    removeFocusElements.forEach(element => {
      if (element) {
        element.addEventListener('focus', () => {
          if ( element === securitycode ) {
            card.classList.add('flipped');
          } else {
            card.classList.remove('flipped');
          }
        })
        if (element === securitycode) {
          element.addEventListener('blur', () => {
            card.classList.remove('flipped');
          })
        }
      }
    });
  }
}

new FrontEnd;

jQuery('body').on('updated_checkout', () => {
  new FrontEnd
});
