class Thankyou {
  constructor() {
    if (!document.querySelector(".vindi_payment_listing")) return;

    this.copyPixLine();
    this.renewPixCharge();
  }

  renewPixCharge() {
    const btn = document.querySelector('#generate_new_qr_code');

    if (btn) {
      btn.addEventListener('click', () => {
        const order = btn.getAttribute('data-order');
        const charge = btn.getAttribute('data-charge');
        const subscription = btn.getAttribute('data-subscription');
        
        jQuery.ajax({
          url: ajax_object.ajax_url,
          type: 'post',
          data: {
              action: 'renew_pix_charge',
              charge_id: charge,
              order_id: order,
              subscription_id: subscription
          },
          success: function(response) {
              window.location.reload();
          }
      });
      });
    }
  }

  copyPixLine() {
    const buttons = document.querySelectorAll('.copy_vindi_line');

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const text = btn.getAttribute('data-code');
        if (navigator?.clipboard?.writeText) {
          navigator.clipboard.writeText(text);
        }
      });
    })
  }
}

new Thankyou;

jQuery('body').on('updated_checkout', () => {
  new Thankyou;
})

