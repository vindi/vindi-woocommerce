<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Vindi Payment BankSlip Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   VindiBankSlipGateway
 * @extends VindiPaymentGateway
 */

class VindiBankSlipGateway extends VindiPaymentGateway
{
  /**
   * Constructor for the gateway.
   */

  public function __construct(VindiSettings $vindi_settings)
  {
    $this->id                   = 'vindi-bank-slip';
    $this->icon                 = apply_filters('vindi_woocommerce_bank_slip_icon', '');
    $this->method_title         = __('Vindi - Boleto Bancário', VINDI);
    $this->method_description   = __('Aceitar pagamentos via boleto bancário utilizando a vindi.', VINDI);
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

    // Load the settings.
    $this->init_settings();

    add_action('woocommerce_thankyou_' . $this->id, array(&$this, 'thank_you_page'));

    parent::__construct($vindi_settings);
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->enabled = $this->get_option('enabled');

  }

  /**
   * Should return payment type for payment processing.
   * @return string
   */
  public function type()
  {
    return 'bank_slip';
  }

  public function init_form_fields()
  {

    $this->form_fields = array(
      'enabled'         => array(
        'title'       => __('Habilitar/Desabilitar', VINDI),
        'label'       => __('Habilitar pagamento por Boleto Bancário com Vindi', VINDI),
        'type'        => 'checkbox',
        'default'     => 'no',
      ),
      'title'           => array(
        'title'       => __('Título', VINDI),
        'type'        => 'text',
        'description' => __('Título que o cliente verá durante o processo de pagamento.', VINDI),
        'default'     => __('Boleto Bancário', VINDI),
      )
    );
  }

  public function payment_fields()
  {
    $user_country = $this->get_country_code();

    if (empty($user_country)) {
      _e('Selecione o País para visualizar as formas de pagamento.', VINDI);
      return;
    }

    if (!$this->routes->acceptBankSlip()) {
      _e('Este método de pagamento não é aceito.', VINDI);
      return;
    }

    $is_single_order = $this->is_single_order();

    if ($is_trial = $this->vindi_settings->get_is_active_sandbox())
      $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();
    
    $this->vindi_settings->get_template('bankslip-checkout.html.php', compact('is_trial', 'is_single_order'));
  }

  public function thank_you_page($order_id)
  {
    if ($download_url = get_post_meta($order_id, 'vindi_wc_invoice_download_url', true)) {
      $this->vindi_settings->get_template('bankslip-download.html.php', compact('download_url'));
    }
  }
}
