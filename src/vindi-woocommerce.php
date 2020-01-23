<?php

global $path;

require_once $path . 'includes/utils/AbstractInstance.php';

class WC_Vindi_Payment extends AbstractInstance {


	/**
	 * Plugin version.
	 *
	 * @var string
	 */

	const CLIENT_NAME = 'plugin-vindi-woocommerce';
	const CLIENT_VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */

	protected static $instance = null;

  public function __construct() {


    // Checks if Woocommerce is installed and activated
    if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {

      $this->includes();

      $this->languages = new VindiLanguages();

    } else {

      add_action( 'admin_notices', array( $this, 'dependencies_notices' ) );
    }

  }

  private function includes() {
    require_once $this->getPath() . '/includes/Languages.php';
  }

  /**
   * Dependencies notices.
   */
  public function dependencies_notices() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
      include_once 'views/woocommerce-missing.php';

      deactivate_plugins( '/vindi-plugin/index.php', true );
    }

    if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
      include_once 'views/ecfb-missing.php';

      deactivate_plugins( '/vindi-plugin/index.php', true );
    }
  }

  public function getPath() {
    return $path;
  }

  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }
}

add_action( 'plugins_loaded', array( 'WC_Vindi_Payment', 'get_instance' ) );
