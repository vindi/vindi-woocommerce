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
 
  public function __construct(VindiSettings $vindi_settings, VindiControllers $controllers)
  {

    global $woocommerce;

    $this->id                   = 'vindi-credit-card';
    $this->icon                 = apply_filters('vindi_woocommerce_credit_card_icon', '');
    $this->method_title         = __('Vindi - Cartão de Crédito', VINDI);
    $this->method_description   = __('Aceitar pagamentos via cartão de crédito utilizando a Vindi.', VINDI);
    $this->has_fields           = true;

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
    $this->init_settings();

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

    $this->form_fields = array(
      'enabled' => array(
        'title'   => __('Habilitar/Desabilitar', VINDI),
        'label'   => __('Habilitar pagamento via Cartão de Crédito com a Vindi', VINDI),
        'type'    => 'checkbox',
        'default' => 'no',
      ),
      'title' => array(
        'title'       => __('Título', VINDI),
        'type'        => 'text',
        'description' => __('Título que o cliente verá durante o processo de pagamento.', VINDI),
        'default'     => __('Cartão de Crédito', VINDI),
      ),
      'verify_method' => array(
        'title'       => __('Transação de Verificação', VINDI),
        'type'        => 'checkbox',
        'description' => __(' Realiza a transação de verificação em todos os novos pedidos. (Taxas adicionais por verificação poderão ser cobradas).', VINDI),
        'default'     => 'no',
      ),
      'single_charge' => array(
        'title' => __('Vendas Avulsas', VINDI),
        'type'  => 'title',
      ),
      'smallest_installment' => array(
        'title'       => __('Valor mínimo da parcela', VINDI),
        'type'        => 'text',
        'description' => __('Valor mínimo da parcela, não deve ser inferior a R$ 5,00.', VINDI),
        'default'     => '5',
      ),
      'installments' => array(
        'title'       => __('Número máximo de parcelas', VINDI),
        'type'        => 'select',
        'description' => __('Número máximo de parcelas para vendas avulsas. Deixe em 1x para desativar o parcelamento.', VINDI),
        'default'     => '1',
        'options'     => array(
          '1'  => '1x',
          '2'  => '2x',
          '3'  => '3x',
          '4'  => '4x',
          '5'  => '5x',
          '6'  => '6x',
          '7'  => '7x',
          '8'  => '8x',
          '9'  => '9x',
          '10' => '10x',
          '11' => '11x',
          '12' => '12x',
        ),
      ),
      'enable_interest_rate' => array(
        'title'       => __('Habilitar juros', VINDI),
        'type'        => 'checkbox',
        'description' => __('Habilitar juros no parcelamento do pedido.', VINDI),
        'default'     => 'no',
      ),
      'interest_rate' => array(
        'title'       => __('Taxa de juros ao mês (%)', VINDI),
        'type'        => 'text',
        'description' => __('Taxa de juros que será adicionada aos pagamentos parcelados.', VINDI),
        'default'     => '0.1',
      )
    );
  }

  public function payment_fields()
  {
    $id = $this->id;
    $is_trial = $this->is_trial;

    $cart = $this->vindi_settings->woocommerce->cart;
    $total = $cart->total;
    foreach ($cart->get_fees() as $index => $fee) {
      if($fee->name == __('Juros', VINDI)) {
        $total -= $fee->amount;
      }
    }

    $max_times  = 12;
    $max_times  = $this->get_order_max_installments($total);
    
    if ($max_times > 1) {
      for ($times = 1; $times <= $max_times; $times++) {
        if ($this->is_interest_rate_enabled()) {
          $installments[$times] = ($total * (1 + (($this->get_interest_rate() / 100) * ($times - 1)))) / $times;
        } else {
          $installments[$times] = ceil($total / $times * 100) / 100;
        }
      }
    }

    $user_payment_profile = $this->build_user_payment_profile();
    $payment_methods = $this->routes->getPaymentMethods();

    if ($payment_methods === false || empty($payment_methods) || ! count($payment_methods['credit_card'])) {
      _e('Estamos enfrentando problemas técnicos no momento. Tente novamente mais tarde ou entre em contato.', VINDI);
      return;
    }

    if ($is_trial = $this->vindi_settings->get_is_active_sandbox())
      $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();

    $this->vindi_settings->get_template('creditcard-checkout.html.php', compact(
      'installments',
      'is_trial',
      'user_payment_profile',
      'payment_methods'
    ));
  }

  public function verify_user_payment_profile()
  {
    $old_payment_profile = (int) filter_input(
      INPUT_POST,
      'vindi-old-cc-data-check',
      FILTER_SANITIZE_NUMBER_INT
    );

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
    if($this->is_single_order()) {
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

    if (!isset($payment_profile) || $current_customer['code'] != $user_vindi_id) {
      $payment_profile = $this->routes->getPaymentProfile($user_vindi_id);
    }

    if($payment_profile['type'] !== 'PaymentProfile::CreditCard')
      return $user_payment_profile;

    if(false === empty($payment_profile)) {
      $user_payment_profile['holder_name']     = $payment_profile['holder_name'];
      $user_payment_profile['payment_company'] = $payment_profile['payment_company']['code'];
      $user_payment_profile['card_number']     = sprintf('**** **** **** %s', $payment_profile['card_number_last_four']);
    }

    WC()->session->set('current_payment_profile', $payment_profile); 
    return $user_payment_profile;
  }

  protected function get_installments()
  {
    if($this->is_single_order())
      return $this->installments;

    $installments = 0;

    foreach($this->vindi_settings->woocommerce->cart->cart_contents as $item) {
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
    
    if($installments != 0)
      return $installments;
    else
      return 1;
  }
}
