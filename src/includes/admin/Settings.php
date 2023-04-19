<?php

namespace VindiPaymentGateways;

use WC_Settings_API;

class VindiSettings extends WC_Settings_API
{

  /**
   * @var WooCommerce
   **/
  public $woocommerce;

  /**
   * @var VindiDependencies
   **/
  public $dependencies;

  /**
   * @var string
   **/
  private $token;

  /**
   * @var WcVindiPayment
   **/
  private $plugin;

  /**
   * @var VindiLogger
   **/
  public $logger;

  /**
   * @var VindiApi
   **/
  public $api;

  /**
   * @var VindiRoutes
   **/
  public $routes;

  /**
   * @var boolean
   **/
  private $debug;

  /**
   * @var boolean
   **/
  private $invalidApiKey;

  function __construct()
  {
    global $woocommerce;
    $this->token = sanitize_file_name(wp_hash(VINDI));
    $this->init_settings();
    $this->init_form_fields();
    $this->debug = $this->get_option('debug') == 'yes' ? true : false;
    $this->logger = new VindiLogger(VINDI, $this->debug);
    $this->api = new VindiApi($this->get_api_key(), $this->logger, $this->get_is_active_sandbox());
    $this->routes = new VindiRoutes($this);
    $this->woocommerce = $woocommerce;
    $this->dependencies  = new VindiDependencies;
    $this->invalidApiKey = get_option( 'vindi_invalid_api_key', false );
    
    if (is_admin()) {
            $this->call_actions();
    }
  }

    public function call_actions()
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_settings_vindi', array(&$this, 'settings_tab'));
        add_action('woocommerce_update_options_settings_vindi', array(&$this, 'process_admin_options'), 10);
        add_action('woocommerce_update_options_settings_vindi', array($this, 'api_key_field'), 11);
        add_action('woocommerce_settings_tabs_settings_vindi', array($this, 'is_api_key_valid'));
        add_action('woocommerce_coupon_options', 'VindiPaymentGateways\CouponsMetaBox::output', 40, 2);
        add_action('woocommerce_coupon_options_save', 'VindiPaymentGateways\CouponsMetaBox::save', 10, 2);
        add_action(
            'woocommerce_coupon_discount_types',
            'VindiPaymentGateways\CouponsMetaBox::remove_ws_recurring_discount',
            10,
            1
        );
        add_action('admin_notices', array(&$this, 'wcs_automatic_payment_settings'));
    }

  /**
   * Create settings tab
   */
  public static function add_settings_tab($settings_tabs)
  {
    $settings_tabs['settings_vindi'] = __('Vindi', VINDI);

    return $settings_tabs;
  }

  /**
   * Include Settings View
   */
  public function settings_tab()
  {
    $this->get_template('admin-settings.html.php', array('settings' => $this));
  }

  /**
   * WC Get Template helper.
   *
   * @param string    $name
   * @param array     $args
   */
  public function get_template($name, $args = array())
  {
    wc_get_template(
      $name,
      $args,
      '',
      sprintf(
          '%s/../../%s',
          dirname(__FILE__),
          WcVindiPayment::TEMPLATE_DIR
      )
    );
  }

  /**
   * Initialize Gateway Settings Form Fields
   */
  function init_form_fields()
  {
    $url           = admin_url(sprintf('admin.php?page=wc-status&tab=logs&log_file=%s-%s-log', VINDI, $this->get_token()));
    $logs_url      = '<a href="' . $url . '" target="_blank">' . __('Ver Logs', VINDI) . '</a>';
    $nfe_know_more = '<a href="http://atendimento.vindi.com.br/hc/pt-br/articles/204450944-Notas-fiscais" target="_blank">' . __('Saiba mais', VINDI) . '</a>';

    $prospects_url = '<a href="https://app.vindi.com.br/prospects/new" target="_blank">' . __('Não possui uma conta?', VINDI) . '</a>';

    $sand_box_article = '<a href="https://atendimento.vindi.com.br/hc/pt-br/articles/115012242388-Sandbox" target="_blank">' . __('Dúvidas?', VINDI) . '</a>';

    $this->form_fields = array(
      'api_key'              => array(
        'title'            => __('Chave da API Vindi', VINDI),
        'type'             => $this->checkKey(),
        'description'      => __('A Chave da API de sua conta na Vindi. ' . $prospects_url, VINDI),
        'default'          => '',
      ),
      'send_nfe_information' => array(
        'title'            => __('Emissão de NFe\'s', VINDI),
        'label'            => __('Enviar informações para emissão de NFe\'s', VINDI),
        'type'             => 'checkbox',
        'description'      => sprintf(__('Envia informações de RG e Inscrição Estadual para Emissão de NFe\'s com nossos parceiros. %s', VINDI), $nfe_know_more),
        'default'          => 'no',
      ),
      'return_status'        => array(
        'title'            => __('Status de conclusão do pedido', VINDI),
        'type'             => 'select',
        'description'      => __('Status que o pedido deverá ter após receber a confirmação de pagamento da Vindi.', VINDI),
        'default'          => 'processing',
        'options'          => array(
          'processing'   => 'Processando',
          'on-hold'      => 'Aguardando',
          'completed'    => 'Concluído',
        ),
      ),
      'vindi_synchronism'        => array(
        'title'            => __('Sincronismo de Status das Assinaturas', VINDI),
        'type'             => 'checkbox',
        'label'      => __('Enviar alterações de status nas assinaturas do WooCommerce', VINDI),
        'description'      => __('Envia as alterações de status nas assinaturas do WooCommerce para Vindi.', VINDI),
        'default'          => 'no',
      ),
      'shipping_and_tax_config'  => array(
        'title'            => __('Cobrança única', VINDI),
        'type'             => 'checkbox',
        'label'      => __('Ativar cobrança única para fretes e taxas', VINDI),
        'description'      => __('Fretes e Taxas serão cobrados somente no primeiro ciclo de uma assinatura', VINDI),
        'default'          => 'no',
      ),
      'testing'              => array(
        'title'            => __('Testes', VINDI),
        'type'             => 'title',
      ),
      'sandbox'             => array(
        'title'            => __('Ambiente Sandbox', VINDI),
        'label'            => __('Ativar Sandbox', VINDI),
        'type'             => 'checkbox',
        'description'      => __('Ative esta opção para habilitar a comunicação com o ambiente Sandbox da Vindi.', VINDI),
        'default'          => 'no',
      ),
      'debug'                => array(
        'title'            => __('Log de Depuração', VINDI),
        'label'            => __('Ativar Logs', VINDI),
        'type'             => 'checkbox',
        'description'      => sprintf(__('Ative esta opção para habilitar logs de depuração do servidor. %s', VINDI), $logs_url),
        'default'          => 'no',
      ),
    );
  }


  /**
   * Get Uniq Token Access
   *
   * @return string
   **/
  public function get_token()
  {
    return $this->token;
  }

  /**
   * Ocult valid token
   *
   * @return string
   **/
  function checkKey()
  {

    return 'text';
  }

  /**
   * Get Vindi API Key
   * @return string
   **/
  public function get_api_key()
  {
    return $this->settings['api_key'];
  }

  /**
   * Return
   * @return boolean
   **/
  public function get_is_active_sandbox()
  {
    return $this->settings['sandbox'];
  }

  /**
   * Check if SSL is enabled when merchant is not in sandbox.
   * @return boolean
   */
  public function check_ssl()
  {
    if ($this->get_is_active_sandbox()) {
      return true;
    } else {
      return is_ssl();
    }
  }

  /**
   * Validate API key field
   * @param string $text
   * @return string $text
   */

  public function api_key_field()
  {
    $api_key = $this->get_api_key();
    $this->api = new VindiApi($api_key, $this->logger, $this->get_is_active_sandbox());

    if (!$api_key) {
      return;
    }
    if ('unauthorized' === $this->api->test_api_key($api_key)) {
      update_option('vindi_invalid_api_key', true);
      $this->invalidApiKey = true;

      include_once VINDI_SRC . 'views/invalid-api-key.php';
    } else {
      update_option('vindi_invalid_api_key', false);
      $this->invalidApiKey = false;
    }
  }

  /**
   * Check if invalidApiKey is true
   * @return mixed includes the invalid-api-key view
   */
  public function is_api_key_valid()
  {
    if($this->invalidApiKey) {
      include_once VINDI_SRC . 'views/invalid-api-key.php';
    }
  }

  /**
   * Get Vindi Shipping and Tax config
   * @return string
   */
  public function get_shipping_and_tax_config()
  {
    return 'yes' === $this->settings['shipping_and_tax_config'];
  }

  public function get_return_status()
  {
    if(isset($this->settings['return_status'])) {
      return $this->settings['return_status'];
    } else {
      return 'processing';
    }
  }

	public function get_webhooks_url() {
		return sprintf('%s/index.php/wc-api/%s?token=%s',
            get_site_url(),
            WcVindiPayment::WC_API_CALLBACK,
            $this->get_token()
    );
  }
  
  /**
   * Get Vindi Synchronism status
   * @return string
   */
  public function get_synchronism_status()
  {
    return 'yes' === $this->settings['vindi_synchronism'];
  }
  
  /**
   * Verify if the user wants to send nfe information
   * @return bool
   */
  public function send_nfe_information()
  {
    return 'yes' === $this->settings['send_nfe_information'];
  }

    /**
    * Warning if WCS automatic payments settings are disabled
    **/
    public function wcs_automatic_payment_settings()
    {
        $opt = get_option('woocommerce_subscriptions_turn_off_automatic_payments');

        if ('yes' !== $opt) {
            $this->get_template(
                'wcs-automatic-payment-deactivated-message.html.php'
            );
        }
        
        return;
    }
}
