<?php

namespace VindiPaymentGateways;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vindi Payment PIX Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   VindiPixGateway
 * @extends VindiPaymentGateway
 */

class VindiPixGateway extends VindiPaymentGateway
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
     * Constructor for the gateway.
     */

    public function __construct(VindiSettings $vindi_settings, VindiControllers $controllers)
    {
        $this->id                   = 'vindi-pix';
        $this->icon                 = apply_filters('vindi_woocommerce_pix_icon', '');
        $this->method_title         = __('Vindi - PIX', VINDI);
        $this->method_description   = __('Aceitar pagamentos via boleto bancário utilizando a Vindi.', VINDI);
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
            'pre-orders'
        );

        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        add_action('woocommerce_view_order', array(&$this, 'show_pix_download'), -10, 1);
        add_action('woocommerce_thankyou_' . $this->id, array(&$this, 'thank_you_page'));

        parent::__construct($vindi_settings, $controllers);
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
        return 'pix';
    }

    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled'         => array(
                'title'       => __('Habilitar/Desabilitar', VINDI),
                'label'       => __('Habilitar pagamento por PIX com Vindi', VINDI),
                'type'        => 'checkbox',
                'default'     => 'no',
            ),
            'title'           => array(
                'title'       => __('Título', VINDI),
                'type'        => 'text',
                'description' => __('Título que o cliente verá durante o processo de pagamento.', VINDI),
                'default'     => __('PIX', VINDI),
            )
        );
    }

    # Essa função é responsável por verificar a compra que está sendo feita
    # No caso de uma assinatura única, o $order[0] não existirá e retornará ela mesmo
    # Issue: https://github.com/vindi/vindi-woocommerce/issues/75
    public function pix_quantity_to_render($order)
    {
        if (!isset($order[0])) {
            return $order;
        }

        return $order[0];
    }

    public function payment_fields()
    {
        $user_country = $this->get_country_code();

        if (empty($user_country)) {
            _e('Selecione o País para visualizar as formas de pagamento.', VINDI);
            return;
        }

        $is_single_order = $this->is_single_order();

        if ($is_trial = $this->vindi_settings->get_is_active_sandbox())
            $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();

        $this->vindi_settings->get_template('pix-checkout.html.php', compact('is_trial', 'is_single_order'));
    }

    public function thank_you_page($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() == 'vindi-pix') {
            $vindi_order = get_post_meta($order_id, 'vindi_order', true);
            $order_to_iterate = $this->pix_quantity_to_render($vindi_order);
            $this->vindi_settings->get_template(
                'pix-download.html.php',
                compact('vindi_order', 'order_to_iterate')
            );
        }
    }

    public function show_pix_download($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() == 'vindi-pix') {
            $vindi_order = get_post_meta($order_id, 'vindi_order', true);
            $order_to_iterate = $this->pix_quantity_to_render($vindi_order);
            if (!$order->is_paid() && !$order->has_status('cancelled')) {
                $this->vindi_settings->get_template(
                    'pix-download.html.php',
                    compact('vindi_order', 'order_to_iterate')
                );
            }
        }
    }
}
