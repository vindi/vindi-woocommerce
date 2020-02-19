<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Vindi Payment Credit Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   VindiCreditGateway
 * @extends VindiPaymentGateway
 */

class VindiCreditGateway extends VindiPaymentGateway
{

  /**
   * Constructor for the gateway.
   */

  public function __construct()
  {

    global $woocommerce;

    $this->id                   = 'vindi-credit-card';
    $this->icon                 = apply_filters('vindi_woocommerce_credit_card_icon', '');
    $this->method_title         = __('Vindi - Credit card', 'vindi-woocommerce');
    $this->method_description   = __('Accept credit card payments using Vindi.', 'vindi-woocommerce');
    $this->has_fields           = true;
    $this->view_transaction_url = 'https://google.com.br';

    $this->supports             = array(
      'subscriptions',
      'products',
      'subscription_cancellation',
      'subscription_reactivation',
      'subscription_suspension',
      'subscription_amount_changes',
      'subscription_payment_method_change',
      'subscription_payment_method_change_customer',
      'subscription_payment_method_change_admin',
      'subscription_date_changes',
      'multiple_subscriptions',
      'refunds',
      'pre-orders'
    );

    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->enabled = $this->get_option('enabled');
    $this->testmode = 'yes' === $this->get_option('testmode');
    $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
    $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');


    // This action hook saves the settings
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

    // We need custom JavaScript to obtain a token
    add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
  }

  public function init_form_fields()
  {

    $this->form_fields = array(
      'enabled' => array(
        'title'       => 'Enable/Disable',
        'label'       => 'Enable Misha Gateway',
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
      ),
      'title' => array(
        'title'       => 'Title',
        'type'        => 'text',
        'description' => 'This controls the title which the user sees during checkout.',
        'default'     => 'Credit Card',
        'desc_tip'    => true,
      ),
      'description' => array(
        'title'       => 'Description',
        'type'        => 'textarea',
        'description' => 'This controls the description which the user sees during checkout.',
        'default'     => 'Pay with your credit card via our super-cool payment gateway.',
      ),
      'testmode' => array(
        'title'       => 'Test mode',
        'label'       => 'Enable Test Mode',
        'type'        => 'checkbox',
        'description' => 'Place the payment gateway in test mode using test API keys.',
        'default'     => 'yes',
        'desc_tip'    => true,
      ),
      'test_publishable_key' => array(
        'title'       => 'Test Publishable Key',
        'type'        => 'text'
      ),
      'test_private_key' => array(
        'title'       => 'Test Private Key',
        'type'        => 'password',
      ),
      'publishable_key' => array(
        'title'       => 'Live Publishable Key',
        'type'        => 'text'
      ),
      'private_key' => array(
        'title'       => 'Live Private Key',
        'type'        => 'password'
      )
    );
  }

  public function payment_scripts()
  {

    // we need JavaScript to process a token only on cart/checkout pages, right?
    if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
      return;
    }

    // if our payment gateway is disabled, we do not have to enqueue JS too
    if ('no' === $this->enabled) {
      return;
    }

    // no reason to enqueue JavaScript if API keys are not set
    if (empty($this->private_key) || empty($this->publishable_key)) {
      return;
    }

    // do not work with card detailes without SSL unless your website is in a test mode
    if (!$this->testmode && !is_ssl()) {
      return;
    }

    // let's suppose it is our payment processor JavaScript that allows to obtain a token
    wp_enqueue_script('misha_js', 'https://www.mishapayments.com/api/token.js');

    // and this is our custom JS in your plugin directory that works with token.js
    wp_register_script('woocommerce_misha', plugins_url('misha.js', __FILE__), array('jquery', 'misha_js'));

    // in most payment processors you have to use PUBLIC KEY to obtain a token
    wp_localize_script('woocommerce_misha', 'misha_params', array(
      'publishableKey' => $this->publishable_key
    ));

    wp_enqueue_script('woocommerce_misha');
  }

  public function payment_fields()
  {

    // ok, let's display some description before the payment form
    if ($this->description) {
      // you can instructions for test mode, I mean test card numbers etc.
      if ($this->testmode) {
        $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
        $this->description  = trim($this->description);
      }
      // display the description with <p> tags etc.
      echo wpautop(wp_kses_post($this->description));
    }

    // I will echo() the form, but you can close PHP tags and print it directly in HTML
    echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

    // Add this action hook if you want your custom payment gateway to support it
    do_action('woocommerce_credit_card_form_start', $this->id);

    // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
    echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
      <input id="misha_ccNo" type="text" autocomplete="off">
      </div>
      <div class="form-row form-row-first">
        <label>Expiry Date <span class="required">*</span></label>
        <input id="misha_expdate" type="text" autocomplete="off" placeholder="MM / YY">
      </div>
      <div class="form-row form-row-last">
        <label>Card Code (CVC) <span class="required">*</span></label>
        <input id="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
      </div>
      <div class="clear"></div>';

    do_action('woocommerce_credit_card_form_end', $this->id);

    echo '<div class="clear"></div></fieldset>';
  }


  public function process_payment($order_id)
  {

    print_r($order_id);

    global $woocommerce;

    // we need it to get any order detailes
    $order = wc_get_order($order_id);


    /*
      * Array with parameters for API interaction
     */
    $args = array();

    /*
     * Your API interaction could be built with wp_remote_post()
      */
    $response = wp_remote_post('{payment processor endpoint}', $args);


    if (!is_wp_error($response)) {

      $body = json_decode($response['body'], true);

      // it could be different depending on your payment processor
      if ($body['response']['responseCode'] == 'APPROVED') {

        // we received the payment
        $order->payment_complete();
        $order->reduce_order_stock();

        // some notes to customer (replace true with false to make it private)
        $order->add_order_note('Hey, your order is paid! Thank you!', true);

        // Empty cart
        $woocommerce->cart->empty_cart();

        // Redirect to the thank you page
        return array(
          'result' => 'success',
          'redirect' => $this->get_return_url($order)
        );
      } else {
        wc_add_notice('Please try again.', 'error');
        return;
      }
    } else {
      wc_add_notice('Connection error.', 'error');
      return;
    }
  }
}
