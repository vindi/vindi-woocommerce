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
   * @var int
   */
  private $max_installments = 12;
 
  public function __construct(VindiSettings $vindi_settings)
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

    parent::__construct($vindi_settings);
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
      )
    );
  }

  public function payment_fields()
  {
    $id = $this->id;
    $description = $this->description;
    $is_trial = $this->is_trial;

    $total = $this->vindi_settings->woocommerce->cart->total;
    $max_times  = 12;
    $max_times  = $this->get_order_max_installments($total);
    
    if ($max_times > 1) {
      for ($times = 1; $times <= $max_times; $times++) {
        $installments[$times] = ceil($total / $times * 100) / 100;
      }
    }

    $user_payment_profile = $this->build_user_payment_profile();
    $payment_methods = $this->routes->getPaymentMethods();

    if ($payment_methods === false || empty($payment_methods) || ! count($payment_methods['credit_card'])) {
      _e( 'Estamos enfrentando problemas técnicos no momento. Tente novamente mais tarde ou entre em contato.', VINDI);
      return;
    }

    $months = array();

    for ($i = 1 ; $i <= 12 ; $i++) {
      $timestamp    = mktime( 0, 0, 0, $i, 1);
      $num          = date('m', $timestamp);
      $name         = date('F', $timestamp);
      $months[$num] = __($name);
    }

    $years = array();

    for ($i = date('Y') ; $i <= date('Y') + 15 ; $i++)
      $years[] = $i;

    if ($is_trial = $this->vindi_settings->get_is_active_sandbox())
      $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();

    $this->vindi_settings->get_template('creditcard-checkout.html.php', compact(
      'months',
      'years',
      'installments',
      'is_trial',
      'user_payment_profile',
      'payment_methods'
    ));
  }

  public function process_payment($order_id)
  {

    print_r($order_id);

    global $woocommerce;

    // we need it to get any order detailes
    $order = wc_get_order($order_id);
    $this->logger->log(sprintf("[Order #%s]: iniciando processamento", $order_id));
    $payment = new VindiPaymentProcessor($order, $this, $this->vindi_settings);
    return $payment->process();
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

    foreach($this->vindi_settings->woocommerce->cart->cart_contents as $item) {
      $plan_id = $item['data']->get_meta('vindi_subscription_plan');
      if (!empty($plan_id))
        break;
    }
    
    $current_plan = WC()->session->get('current_plan');
    if ($current_plan && $current_plan['id'] == $plan_id && !empty($current_plan['installments']))
      return $current_plan['installments'];

    $plan = $this->routes->getPlan($plan_id);
    WC()->session->set('current_plan', $plan);
    if($plan['installments'] > 1)
      return $plan['installments'];               
            
    return 1;
  }
}
