class Thankyou {
  constructor() {
    if (!document.querySelector(".vindi_payment_listing")) return;

    this.copyPixLine();
  }

  copyPixLine() {
    const btn = document.querySelector('#copy_vindi_pix_code');

    if(btn) {
      btn.addEventListener('click', () => {
        const text = btn.getAttribute('data-code');
        if (navigator?.clipboard?.writeText) {
          navigator.clipboard.writeText(text);
        }
      });
    }
  }
}

new Thankyou;

jQuery('body').on('updated_checkout', () => {
  new Thankyou;
})

