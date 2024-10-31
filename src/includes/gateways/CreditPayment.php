<?php

namespace VindiPaymentGateways;

use VindiPaymentGateways\VindiFieldsArray;
use VindiPaymentGateways\VindiViewOrderHelpers;
use VindiPaymentGateways\CreditHelpers;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Vindi Payment Credit Card Gateway class.
 * Extended by individual payment gateways to handle payments.
 * @class   VindiCreditGateway
 * @extends VindiPaymentGateway
 */

class VindiCreditGateway extends VindiPaymentGateway
{
  /**
   * @var VindiSettings
   */
    public $vindi_settings;

  /**
   * @var VindiControllers
   */
    public $controllers;

  /**
   * @var int
   */
    private $max_installments = 12;

  /**
   * @var int
   */
    public $interest_rate;

    public $smallest_installment;
    public $installments;
    public $verify_method;
    public $enable_interest_rate;

    public function __construct(VindiSettings $vindi_settings, VindiControllers $controllers)
    {
    global $woocommerce;

    $this->id                   = 'vindi-credit-card';
    $this->icon                 = apply_filters('vindi_woocommerce_credit_card_icon', '');
    $this->method_title         = __('Vindi - Cartão de Crédito', VINDI);
    $this->method_description   = __('Aceitar pagamentos via cartão de crédito utilizando a Vindi.', VINDI);
    $this->has_fields           = true;

        $this->supports = array('subscriptions','products','subscription_cancellation','subscription_reactivation',
        'subscription_suspension','subscription_amount_changes','subscription_payment_method_change',
        'subscription_payment_method_change_customer','subscription_payment_method_change_admin',
        'subscription_date_changes','multiple_subscriptions','refunds','pre-orders'
    );

    $this->init_form_fields();
    $this->init_settings();
            add_action('woocommerce_view_order', array(&$this, 'show_credit_card_download'), -10, 1);
    $this->smallest_installment = $this->get_option('smallest_installment');
    $this->installments = $this->get_option('installments');
    $this->verify_method = $this->get_option('verify_method');
    $this->enable_interest_rate = $this->get_option('enable_interest_rate');
    $this->interest_rate = $this->get_option('interest_rate');
    parent::__construct($vindi_settings, $controllers);
    }

  /**
   * Should return payment type for payment processing.
   * @return string
   */
    public function type()
    {
    return 'cc';
    }

    public function init_form_fields()
    {
        $fields = new VindiFieldsArray();
        $this->form_fields = $fields->fields_array();
    }

    public function payment_fields()
    {
        $cart = $this->vindi_settings->woocommerce->cart;
        $ordeId = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT) ?? absint(get_query_var('order-pay'));
        $total = $this->calculate_total($ordeId, $cart);
        $installments = $this->build_cart_installments($total);
        $user_payment_profile = $this->build_user_payment_profile();
        $payment_methods = $this->routes->getPaymentMethods();
        $credit_card_to_render = new VindiViewOrderHelpers();

        if ($credit_card_to_render->check_payment_methods($payment_methods)) {
            _e('Estamos problemas técnicos no momento. Tente novamente mais tarde ou entre em contato.', VINDI);
      return;
        }

        $is_trial = $this->check_is_trial();

    $this->vindi_settings->get_template('creditcard-checkout.html.php', compact(
        'installments',
        'is_trial',
        'user_payment_profile',
        'payment_methods'
    ));
    }

    private function calculate_total($order_id, $cart)
    {
        $pay_for_order = filter_input(INPUT_GET, 'pay_for_order', FILTER_VALIDATE_BOOLEAN);

        if ($pay_for_order === true) {
            return $this->calculate_order_total($order_id);
        }
        $credit_payment_helpers = new CreditHelpers();
        return $credit_payment_helpers->get_cart_total($cart);
    }

    private function calculate_order_total($order_id)
    {
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $total = $this->subtract_fees($order, $total);
        return $total;
    }

    private function subtract_fees($order, $total)
    {
        foreach ($order->get_items('fee') as $item_fee) {
            if ($item_fee->get_name() == __('Juros', VINDI)) {
                $total -= $item_fee->get_total();
            }
        }
        return $total;
    }

    private function check_is_trial()
    {
        $is_trial = false;
        if (isset($this->is_trial) && $this->is_trial == $this->vindi_settings->get_is_active_sandbox()) {
            $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();
        }
        return $is_trial;
    }

    public function build_cart_installments($total)
    {
        $max_times = $this->get_order_max_installments($total);
        $installments = [];
        if ($max_times > 1) {
            for ($times = 1; $times <= $max_times; $times++) {
                $installments[$times] = $this->get_cart_installments($times, $total);
            }
        }
        return $installments;
    }

    public function get_cart_installments($times, $total)
    {
        if ($this->is_interest_rate_enabled()) {
            return ($total * (1 + (($this->get_interest_rate() / 100) * ($times - 1)))) / $times;
        }
        return ceil($total / $times * 100) / 100;
    }


    public function verify_user_payment_profile()
    {
        $old_payment_profile = (int) filter_input(INPUT_POST, 'vindi-old-cc-data-check', FILTER_SANITIZE_NUMBER_INT);
        return 1 === $old_payment_profile;
    }

    public function verify_method()
    {
        return 'yes' === $this->verify_method;
    }

    public function is_interest_rate_enabled()
    {
        return 'yes' === $this->enable_interest_rate;
    }

    public function get_interest_rate()
    {
        return floatval($this->interest_rate);
    }

    protected function get_order_max_installments($order_total)
    {
        if ($this->is_single_order()) {
            $order_max_times = floor($order_total / $this->smallest_installment);
            $max_times = empty($order_max_times) ? 1 : $order_max_times;
            return min($this->max_installments, $max_times, $this->get_installments());
        }
        return $this->get_installments();
    }

    private function build_user_payment_profile()
    {
        $user_payment_profile = array();
        $user_vindi_id = get_user_meta(wp_get_current_user()->ID, 'vindi_customer_id', true);
        $payment_profile = WC()->session->get('current_payment_profile');
        $current_customer = WC()->session->get('current_customer');
        if (!isset($payment_profile) || ($current_customer['code'] ?? null) != $user_vindi_id) {
            $payment_profile = $this->routes->getPaymentProfile($user_vindi_id);
        }
        if (($payment_profile['type'] ?? null) !== 'PaymentProfile::CreditCard') {
            return $user_payment_profile;
        }
        if (false === empty($payment_profile)) {
            $user_payment_profile['holder_name']     = $payment_profile['holder_name'];
            $user_payment_profile['payment_company'] = $payment_profile['payment_company']['code'];
            $user_payment_profile['card_number']     = sprintf('**** **** **** %s', $payment_profile['card_number_last_four']);
        }
        WC()->session->set('current_payment_profile', $payment_profile);
        return $user_payment_profile;
    }

    protected function get_installments()
    {
      if ($this->is_single_order())
        return $this->installments;
      $installments = 0;
      foreach ($this->vindi_settings->woocommerce->cart->cart_contents as $item) {
          $plan_id = $item['data']->get_meta('vindi_plan_id');

        if (!empty($plan_id)) {
            $plan = $this->routes->getPlan($plan_id);

          if ($installments == 0) {
              $installments = $plan['installments'];
          } elseif ($plan['installments'] < $installments) {
              $installments = $plan['installments'];
          }
        }
      }

      if ($installments != 0)
        return $installments;
      else
        return 1;
    }

    public function show_credit_card_download($order_id)
    {
        $order = wc_get_order($order_id);
        $vindi_order = [];
        $order_to_iterate = 0;

        if ($order->get_meta('vindi_order', true)) {
            $credit_card_to_render = new VindiViewOrderHelpers();
            $vindi_order = $order->get_meta('vindi_order', true);
            $order_to_iterate = $credit_card_to_render->clean_order_data($vindi_order);
            $first_key = key($order_to_iterate);
            $paymentMethod = $order_to_iterate[$first_key]['bill']['payment_method'] ?? null;
        }

        if ($order->get_payment_method() == 'credit_card' || $paymentMethod == 'credit_card') {
            $this->show_credit_card_template($order, $vindi_order, $order_to_iterate);
        }
    }

    private function show_credit_card_template($order, $vindi_order, $order_to_iterate)
    {
        if (!$order->is_paid() && !$order->has_status('cancelled')) {
            $this->vindi_settings->get_template(
                'credit-card-download.html.php',
                compact('vindi_order', 'order_to_iterate')
            );
        }
    }
}
