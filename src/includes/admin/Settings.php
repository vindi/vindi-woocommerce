<?php

class VindiSettings extends WC_Settings_API
{

  /**
   * @var WooCommerce
   **/
  public $woocommerce;

  /**
   * @var string
   **/
  private $token;

  /**
   * @var WC_Vindi_Payment
   **/
  private $plugin;

  /**
   * @var boolean
   **/
  private $debug;


  public function __construct()
  {
    global $woocommerce;

    $this->token = sanitize_file_name(wp_hash(VINDI));

    $this->init_settings();
    $this->init_form_fields();

    $this->woocommerce = $woocommerce;


    if (is_admin()) {

      add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
      add_action('woocommerce_settings_tabs_settings_vindi', array(&$this, 'settings_tab'));
      add_action('woocommerce_update_options_settings_vindi', array(&$this, 'process_admin_options'));
      // add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
    }
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
   * Initialise Gateway Settings Form Fields
   */
  function init_form_fields()
  {


    $prospects_url = '<a href="https://app.vindi.com.br/prospects/new" target="_blank">' . __('Don\'t have an account?', VINDI) . '</a>';

    $this->form_fields = array(
      'api_key'              => array(
        'title'            => __('Vindi API key', VINDI),
        'type'             => 'text',
        'description'      => __('The API Key for your Vindi account. ' . $prospects_url, VINDI),
        'default'          => '',
      ),
    );
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
        WC_Vindi_Payment::TEMPLATE_DIR
      )
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
   * Get Vindi API Key
   * @return string
   **/
  public function get_api_key()
  {
    // if ('yes' === $this->get_is_active_sandbox()) {
    //   return $this->settings['api_key_sandbox'];
    // }

    return $this->settings['api_key'];
  }


  /**
   * Check if SSL is enabled when merchant is not trial.
   * @return boolean
   */
  public static function check_ssl()
  {

    if (WC_Vindi_Payment::MODE != 'development') {
      return false;
    } else {
      return is_ssl();
    }
  }
}
