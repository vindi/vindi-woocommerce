<?php

namespace VindiPaymentGateways;

use Exception;
use WC_Subscriptions_Product;

require_once plugin_dir_path(__FILE__)  . '/utils/AbstractInstance.php';


/**
 * @SuppressWarnings(PHPMD)
 */
class WcVindiPayment extends AbstractInstance
{
    /**
     * @var string
     */
    const TEMPLATE_DIR = '/templates/';

    /**
     * @var string
     */
    const WC_API_CALLBACK = 'vindi_webhook';

    /**
     * Instance of this class.
     *
     * @var object
     */
    static $instance = null;

    /**
     * @var VindiPaymentGateway\VindiLanguages
     */
    private $languages;

    /**
     * @var VindiPaymentGateway\VindiSettings
     */
    private $settings;

    /**
     * @var VindiPaymentGateway\VindiControllers
     */
    private $controllers;

    /**
     * @var VindiPaymentGateway\VindiWebhooks
     */
    private $webhooks;

    /**
     * @var VindiPaymentGateway\FrontendFilesLoader
     */
    private $frontend_files_loader;

    /**
     * @var VindiPaymentGateway\VindiSubscriptionStatusHandler
     */
    private $subscription_status_handler;

    /**
     * @var VindiPaymentGateway\ProductsMetabox
     */
    private $product_metabox;

    /**
     * @var VindiPaymentGateway\VindiProductStatus
     */
    private $vindi_status_notifier;

    /**
     * @var VindiPaymentGateway\InterestPriceHandler
     */
    private $interest_price_handler;

    /**
     * @var VindiWCSRenewalDisable
     */
    private $wcs_renewal_disable;

    public function __construct()
    {

        // Checks if Woocommerce is installed and activated
        $this->init();


        $this->languages = new VindiLanguages();

        $this->settings = new VindiSettings();
        $this->controllers = new VindiControllers($this->settings);
        $this->webhooks = new VindiWebhooks($this->settings);
        $this->frontend_files_loader = new FrontendFilesLoader();
        $this->subscription_status_handler = new VindiSubscriptionStatusHandler($this->settings);
        $this->vindi_status_notifier = new VindiProductStatus($this->settings);
        $this->interest_price_handler = new InterestPriceHandler();
        $this->product_metabox = new ProductsMetabox();
        $this->wcs_renewal_disable = new VindiWCSRenewalDisable();


        /**
         * Add Gateway to Woocommerce
         */
        add_filter('woocommerce_payment_gateways', array(&$this, 'add_gateway'));

        /**
         * Register webhook handler
         */
        add_action('woocommerce_api_' . self::WC_API_CALLBACK, array(
            $this->webhooks, 'handle'
        ));

        add_filter('woocommerce_add_to_cart_validation', [$this, 'limit_same_subscriptions'], 10, 3);
        add_filter('woocommerce_update_cart_validation', [$this, 'limit_duplicate_subscriptions_cart_update'], 10, 4);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'disallow_subscription_single_product_cart'], 10, 4);
        add_filter('woocommerce_cart_needs_payment', [$this, 'filter_woocommerce_cart_needs_payment'], 10, 2);
        add_action('wp_ajax_renew_pix_charge', [$this, 'renew_pix_charge']);
        add_action('wp_ajax_nopriv_renew_pix_charge', [$this, 'renew_pix_charge']);
        do_action('woocommerce_set_cart_cookies', true);
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'button_link_payment'], 20, 4);
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_checkout_gateways'], 10, 1);
        add_action('woocommerce_pay_order_before_payment', [$this, 'add_billing_fields'], 10, 1);
        add_action('woocommerce_before_pay_action', [$this, 'save_billing_fields'], 10, 1);
        add_action( 'wcs_after_parent_order_setup_cart', [$this, 'set_payment_gateway']);
        add_filter('manage_edit-shop_order_columns', [$this,'custom_shop_order_columns'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this,'custom_shop_order_column_data'], 10, 1);
    }

    /**
     * Init the plugin after plugins_loaded so environment variables are set.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function init()
    {
        // Helpers and Languages
        require_once plugin_dir_path(__FILE__) . '/services/Api.php';
        require_once plugin_dir_path(__FILE__) . '/services/Logger.php';
        require_once plugin_dir_path(__FILE__) . '/i18n/Languages.php';
        require_once plugin_dir_path(__FILE__) . '/services/VindiHelpers.php';
        require_once plugin_dir_path(__FILE__) . '/services/Webhooks.php';
        require_once plugin_dir_path(__FILE__) . '/services/WebhooksHelpers.php';


        // Loading Abstract Method and Utils
        require_once plugin_dir_path(__FILE__) . '/utils/PaymentGateway.php';
        require_once plugin_dir_path(__FILE__) . '/utils/Conversions.php';
        require_once plugin_dir_path(__FILE__) . '/utils/RedirectCheckout.php';
        require_once plugin_dir_path(__FILE__) . '/utils/PostMeta.php';

        require_once plugin_dir_path(__FILE__) . '/includes/admin/CouponsMetaBox.php';
        require_once plugin_dir_path(__FILE__) . '/includes/admin/ProductsMetabox.php';
        require_once plugin_dir_path(__FILE__) . '/includes/admin/Settings.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/CreditPayment.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/BankSlipPayment.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/PixPayment.php';
        require_once plugin_dir_path(__FILE__) . '/includes/gateways/BolepixPayment.php';
        require_once plugin_dir_path(__FILE__) . '/utils/SubscriptionStatusHandler.php';
        require_once plugin_dir_path(__FILE__) . '/utils/InterestPriceHandler.php';

        require_once plugin_dir_path(__FILE__) . '/includes/admin/ProductStatus.php';

        // Routes import
        require_once plugin_dir_path(__FILE__) . '/routes/RoutesApi.php';

        // Controllers
        require_once plugin_dir_path(__FILE__) . '/controllers/index.php';

        require_once plugin_dir_path(__FILE__) . '/utils/PaymentProcessor.php';
        require_once plugin_dir_path(__FILE__) . '/utils/WCSRenewalDisable.php';
    }

    public static function getPath()
    {
        return plugin_dir_path(__FILE__);
    }

    public static function get_instance()
    {
        require_once plugin_dir_path(__FILE__) . '/utils/FrontendFilesLoader.php';
        new FrontendFilesLoader();

        if (VindiDependencies::check()) {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    /**
     * Add the gateway to WooCommerce.
     * @param  array $methods WooCommerce payment methods.
     * @return array Payment methods with Vindi.
     */
    public function add_gateway($methods)
    {
        $methods[] = new VindiCreditGateway($this->settings, $this->controllers);
        $methods[] = new VindiBankSlipGateway($this->settings, $this->controllers);
        $methods[] = new VindiPixGateway($this->settings, $this->controllers);
        $methods[] = new VindiBolepixGateway($this->settings, $this->controllers);

        return $methods;
    }

    /**
     * Sobrescreve o método que remove os métodos de pagamento para assinaturas com trial
     * @return bool
     */
    public function filter_woocommerce_cart_needs_payment($needs_payment, $cart)
    {
        if (floatval($cart->total) == 0 || $this->cart_has_trial($cart)) {
            return true;
        }

        return $needs_payment;
    }

    private function cart_has_trial($cart)
    {
        $items = $cart->get_cart();
        foreach ($items as $item) {
            if (
                class_exists('WC_Subscriptions_Product')
                && WC_Subscriptions_Product::get_trial_length($item['product_id']) > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function renew_pix_charge()
    {
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $charge_id = filter_input(INPUT_POST, 'charge_id', FILTER_SANITIZE_NUMBER_INT);
        $subscription_id = filter_input(INPUT_POST, 'subscription_id', FILTER_SANITIZE_NUMBER_INT);

        $order = wc_get_order($order_id);
        $vindi_order = $order->get_meta('vindi_order', true);

        if ($charge_id && $subscription_id) {
            $routes = new VindiRoutes($this->settings);
            $charge = $routes->renewCharge($charge_id);

            if (isset($charge['status']) && isset($charge['last_transaction']['gateway_response_fields'])) {
                $last_transaction = $charge['last_transaction']['gateway_response_fields'];

                $subscription = $vindi_order[$subscription_id];
                $bill = [
                    'id' => $subscription['bill']['id'],
                    'status' => $subscription['bill']['status'],
                    'charge_id' => $charge['id'],
                    'pix_expiration' => $last_transaction['max_days_to_keep_waiting_payment'],
                    'pix_code' => $last_transaction['qrcode_original_path'],
                    'pix_qr' => $last_transaction['qrcode_path'],
                ];

                $vindi_order[$subscription_id]['bill'] = $bill;
                $order->update_meta_data('vindi_order', $vindi_order);
                $order->save();
            }
        }
    }

    public function limit_same_subscriptions($passed, $product_id, $quantity)
    {
        $product = wc_get_product($product_id);

        if ($product->is_virtual()) {
            return $passed;
        }

        if (WC_Subscriptions_Product::is_subscription($product_id)) {
            $subscription_count = $this->get_subscription_count($product_id);

            if ($subscription_count + $quantity > 1) {
                wc_add_notice('Você só pode ter até 1 assinatura do mesmo produto no seu carrinho.', 'error');
                return false;
            }
        }

        return $passed;
    }

    public function get_subscription_count($product_id)
    {
        $cart = WC()->cart->get_cart();
        $subscription_count = 0;

        foreach ($cart as $cart_item) {
            if ($cart_item['data']->get_id() === $product_id) {
                $subscription_count += $cart_item['quantity'];
            }
        }

        return $subscription_count;
    }

    public function limit_duplicate_subscriptions_cart_update($passed, $cart_item_key, $values, $quantity)
    {
        $product_id = $values['product_id'];
        $product = wc_get_product($product_id);

        if ($this->is_virtual_product($product)) {
            return $passed;
        }

        if ($this->subscription_exceeds_limit($product_id, $quantity)) {
            return false;
        }

        return $passed;
    }

    public function is_virtual_product($product)
    {
        return $product->is_virtual();
    }

    public function subscription_exceeds_limit($product_id, $quantity)
    {
        if (WC_Subscriptions_Product::is_subscription($product_id)) {
            $subscription_count = $this->count_subscriptions_in_cart($product_id);

            if ($subscription_count >= 1 && $quantity > 1) {
                $message = 'Você só pode ter até 1 assinatura do mesmo produto no seu carrinho.';
                wc_add_notice(__($message, 'vindi-payment-gateway'), 'error');
                return true;
            }
        }

        return false;
    }

    public function count_subscriptions_in_cart($product_id)
    {
        $subscription_count = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['data']->get_id() === $product_id) {
                $subscription_count++;
            }
        }
        return $subscription_count;
    }

    public function disallow_subscription_single_product_cart($passed, $product_id, $quantity)
    {
        $product = wc_get_product($product_id);

        if ($product->is_virtual()) {
            return $passed;
        }

        if ($this->is_cart_mixed_with_subscription($product_id)) {
            wc_add_notice(__('Olá! Finalize a compra da assinatura adicionada
             ao carrinho antes de adicionar outra assinatura ou produto.', 'vindi-payment-gateway'), 'error');
            return false;
        }

        return $passed;
    }

    public function is_cart_mixed_with_subscription($product_id)
    {
        $cart = WC()->cart->get_cart();
        if (empty($cart)) {
            return false;
        }

        $is_subscription = false;
        $new_product_subscription = WC_Subscriptions_Product::is_subscription($product_id);

        foreach ($cart as $cart_item) {
            if (WC_Subscriptions_Product::is_subscription($cart_item['data']->get_id())) {
                $is_subscription = true;
                break;
            }
        }

        return $is_subscription !== $new_product_subscription;
    }

    public function button_link_payment($order)
    {
        $template_path = plugin_dir_path(__FILE__) . 'templates/admin-payment-button.html.php';
        $has_item = false;
        $has_subscription = false;
        $order_status = $order->get_status();

        if(!$template_path){
            return;
        }

        if (count($order->get_items()) > 0) {
            $order_type = get_post_type($order->get_id());
            $gateway = $order->get_payment_method();
            $has_subscription = $this->has_subscription($order);
            if ($order_type == 'shop_subscription') {
                $parent_order = $order->get_parent();
                if ($parent_order) {
                    $parent_order_id = $parent_order->get_id();
                    $order = wc_get_order($parent_order_id);
                    $has_subscription = true;
                }
            }

            if ($order->get_checkout_payment_url()) {
                $link_payment = $this->build_payment_link($order, $gateway);
            }
            $order_status = $order->get_status();
            $has_item = true;
            $urlAdmin = get_admin_url();
            $urlShopSubscription = "{$urlAdmin}edit.php?post_type=shop_subscription";
        }
        include $template_path;
    }

    public function has_subscription($order){
        $order_items = $order->get_items();
        foreach($order_items as $order_item){
            if(WC_Subscriptions_Product::is_subscription($order_item->get_product_id()) && $order->get_created_via() == 'subscription'){
                return true;
            }
        }
        return false;
    }

    function auto_create_user_for_order($order)
    {
        $billing_email = $order->get_billing_email();
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();

        if (email_exists($billing_email)) {
            $username = strtolower($billing_first_name . '_' . $billing_last_name);
            $suffix = 1;
            while (username_exists($username)) {
                $username = strtolower($billing_first_name . '_' . $billing_last_name . '_' . $suffix);
                $suffix++;
            }
        } else {
            $username = current(explode('@', $billing_email));
        }

        if ($username) {
            $password = wp_generate_password(12, false);
            $user_id = wp_create_user($username, $password, $billing_email);
            $order->set_customer_id($user_id);
            $order->save();
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }
    }

    function build_payment_link($order, $gateway)
    {
        $url = wc_get_checkout_url();
        $gateway = $gateway ? "&vindi-gateway={$gateway}" : '';
        $orderId = $order->get_id();
        $orderKey = $order->get_order_key();

        return "{$url}order-pay/{$orderId}/?pay_for_order=true&key={$orderKey}&vindi-payment-link=true{$gateway}";
    }

    public function filter_checkout_gateways($gateways)
    {
        $available = [
            'vindi-bank-slip',
            'vindi-bolepix',
            'vindi-credit-card',
            'vindi-pix',
        ];

        $isPaymentLink = '';
        $gateway = '';

        if (WC()->session) {
            $isPaymentLink = WC()->session->get('vindi-payment-link');
            $gateway = WC()->session->get('vindi-gateway');
        }
        if (!$isPaymentLink) {
            $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        }
        if (!$gateway) {
            $gateway = filter_input(INPUT_GET, 'vindi-gateway') ?? false;
        }

        if ($isPaymentLink) {
            $items = array_diff(array_keys($gateways), $available);
            foreach ($items as $item) {
                if (isset($gateways[$item])) {
                    unset($gateways[$item]);
                }
            }
            if ($gateway && in_array($gateway, array_keys($gateways))) {
                return [$gateway => $gateways[$gateway]];
            }
        }
        return $gateways;
    }

    public function add_billing_fields()
    {
        $template_path = plugin_dir_path(__FILE__) . 'templates/fields-order-pay-checkout.php';
        if(!$template_path){
            return;
        }

        $orderId = absint( get_query_var('order-pay') );

        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;

        if ($isPaymentLink) {
            $order = wc_get_order( $orderId );
            $fields = WC()->checkout->get_checkout_fields('billing');
            include $template_path;
        }
    }

    public function save_billing_fields($order)
    {
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        if (!$isPaymentLink) {
            return;
        }

        $fields = [
            'first_name',
            'last_name',
            'persontype',
            'cpf',
            'company',
            'cnpj',
            'country',
            'postcode',
            'address_1',
            'number',
            'address_2',
            'neighborhood',
            'city',
            'state',
            'phone',
            'email'
        ];

        try {
            $this->validate_required_fields();

            foreach ($fields as $key) {
                $field = filter_input(INPUT_POST, "billing_$key", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;
                if ($field) {
                    if (method_exists($order, "set_billing_$key")) {
                        $order->{"set_billing_$key"}($field);
                    } else {
                        $order->update_meta_data("_billing_$key", $field);
                    }

                    if (method_exists($order, "set_shipping_$key")) {
                        $order->{"set_shipping_$key"}($field);
                    }
                }
            }

            $order->save();
            $this->auto_create_user_for_order($order);
        } catch (Exception $err) {
            wc_add_notice($err->getMessage(), 'error');
        }

    }

    public function validate_required_fields()
    {
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        if (!$isPaymentLink) {
            return;
        }
        $required = [
            'first_name'   => __('Nome', 'vindi-payment-gateway'),
            'last_name'    => __('Sobrenome', 'vindi-payment-gateway'),
            'persontype'   => __('Tipo de Pessoa', 'vindi-payment-gateway'),
            'country'      => __('País', 'vindi-payment-gateway'),
            'postcode'     => __('CEP', 'vindi-payment-gateway'),
            'address_1'    => __('Rua', 'vindi-payment-gateway'),
            'number'       => __('Número', 'vindi-payment-gateway'),
            'neighborhood' => __('Bairro', 'vindi-payment-gateway'),
            'city'         => __('Cidade', 'vindi-payment-gateway'),
            'state'        => __('Estado', 'vindi-payment-gateway'),
            'phone'        => __('Celular', 'vindi-payment-gateway'),
            'email'        => __('E-mail', 'vindi-payment-gateway')
        ];

        foreach ($required as $key => $value) {
            $field = filter_input(INPUT_POST, "billing_$key", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;
            if (!$field) {
                throw new Exception($value);
            }
        }

        $this->validate_person_type();
    }

    public function validate_person_type()
    {
        $person = filter_input(INPUT_POST, "billing_persontype", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;
        $cpf = filter_input(INPUT_POST, "billing_cpf", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;
        $cnpj = filter_input(INPUT_POST, "billing_cnpj", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;

        if ($person === '1' && !$cpf) {
            throw new Exception((__('CPF', 'vindi-payment-gateway')));
        }

        if ($person === '2' && !$cnpj) {
            throw new Exception((__('CNPJ', 'vindi-payment-gateway')));
        }
    }

    public function set_payment_gateway(){
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link', FILTER_VALIDATE_BOOLEAN) ?? false;
        $gateway = filter_input(INPUT_GET, 'vindi-gateway', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        WC()->session->set('vindi-payment-link',$isPaymentLink);
        WC()->session->set('vindi-gateway',$gateway);
    }

    public function custom_shop_order_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_status' || $key === 'Status') {
                $new_columns['vindi_payment_link'] = __('Link de Pagamento', 'vindi-payment-gateway');
            }
        }
        return $new_columns;
    }

    function custom_shop_order_column_data($column)
    {
        global $post;
        $template_path = plugin_dir_path(__FILE__) . 'templates/admin-payment-link-button.php';
        if (!$template_path) {
            return;
        }
        $link_payment = '';
        if ($column === 'vindi_payment_link') {
            $order = wc_get_order($post->ID);
            if (count($order->get_items()) > 0) {
                $order_status = $order->get_status();
                $gateway = $order->get_payment_method();
                $link_payment = $this->build_payment_link($order, $gateway);
                include $template_path;
            }
        }
    }
}

add_action('plugins_loaded', array(WcVindiPayment::class, 'get_instance'));
