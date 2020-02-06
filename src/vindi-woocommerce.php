<?php

require_once plugin_dir_path(__FILE__) . 'utils/AbstractInstance.php';

class WC_Vindi_Payment extends AbstractInstance
{

    /**
     * Instance of this class.
     *
     * @var object
     */

    protected static $instance = null;

    public function __construct()
    {

        // Checks if Woocommerce is installed and activated
        if (class_exists('WC_Payment_Gateway') && class_exists('Extra_Checkout_Fields_For_Brazil')) {

            $this->includes();

            $this->languages = new VindiLanguages();
            $this->supports = new SupportSubscriptions();

            /**
             * Add Gateway to Woocommerce
             */
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));

        } else {

            add_action('admin_notices', array($this, 'dependencies_notices'));
        }

    }

    private function includes()
    {
        require_once $this->getPath() . '/includes/Languages.php';
        require_once $this->getPath() . '/SupportSubscriptions.php';
    }

    /**
     * Dependencies notices.
     */

    public function dependencies_notices()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            include_once 'views/woocommerce-missing.php';

            deactivate_plugins('/vindi-plugin/index.php', true);
        }

        if (!class_exists('Extra_Checkout_Fields_For_Brazil')) {
            include_once 'views/ecfb-missing.php';

            deactivate_plugins('/vindi-plugin/index.php', true);
        }
    }

    /**
     * Add the gateway to WooCommerce.
     *
     * @param  array $methods WooCommerce payment methods.
     *
     * @return array Payment methods with Iugu.
     */

    public function add_gateway($methods)
    {

        $methods[] = 'WC_Vindi_Credit_Gateway';

        return $methods;
    }

    public static function getPath()
    {
        return plugin_dir_path(__FILE__);
    }

    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}

add_action('plugins_loaded', array('WC_Vindi_Payment', 'get_instance'));
