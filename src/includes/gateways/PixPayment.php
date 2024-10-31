<?php

namespace VindiPaymentGateways;

/**
 * Vindi Payment PIX Gateway class.
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
    public $vindiSettings;

    /**
     * @var VindiControllers
     */
    public $controllers;

    /**
     * Constructor for the gateway.
     */
    public function __construct(VindiSettings $vindiSettings, VindiControllers $controllers)
    {
        $this->id                   = 'vindi-pix';
        $this->icon                 = apply_filters('vindi_woocommerce_pix_icon', '');
        $this->method_title         = __('Vindi - PIX', VINDI);
        $this->method_description   = __('Aceitar pagamentos via PIX utilizando a Vindi.', VINDI);
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
        $this->init_settings();
        add_action('woocommerce_view_order', array(&$this, 'show_pix_download'), -10, 1);
        add_action('woocommerce_thankyou_' . $this->id, array(&$this, 'thank_you_page'));
        parent::__construct($vindiSettings, $controllers);
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
        $filtered_order = array_filter($order, function ($value) {
            return !empty($value) && is_array($value);
        });
        return $filtered_order;
    }

    public function payment_fields()
    {
        $user_country = $this->get_country_code();

        if (empty($user_country)) {
            _e('Selecione o País para visualizar as formas de pagamento.', VINDI);
            return;
        }

        $is_single_order = $this->is_single_order();
        $is_trial = $this->vindi_settings->get_is_active_sandbox();
        if ($is_trial) {
            $is_trial = $this->routes->isMerchantStatusTrialOrSandbox();
        }

        $this->vindi_settings->get_template('pix-checkout.html.php', compact('is_trial', 'is_single_order'));
    }

    public function thank_you_page($order_id)
    {
        $order = wc_get_order($order_id);
        $vindi_order = [];
        $order_to_iterate = 0;

        if ($order->get_payment_method() == 'vindi-pix') {
            $vindi_order = $order->get_meta('vindi_order', true);
            $order_to_iterate = $this->pix_quantity_to_render($vindi_order);
            $this->vindi_settings->get_template(
                'pix-download.html.php',
                compact('vindi_order', 'order_to_iterate', 'order_id')
            );
        }
    }

    public function show_pix_download($order_id)
    {
        $order = wc_get_order($order_id);
        $vindi_order = [];
        $order_to_iterate = 0;

        if ($order->get_meta('vindi_order', true)) {
            $vindi_order = $order->get_meta('vindi_order', true);
            $order_to_iterate = $this->pix_quantity_to_render($vindi_order);
            $first_key = key($order_to_iterate);
            $paymentMethod = $order_to_iterate[$first_key]['bill']['payment_method'] ?? null;
        }

        if ($order->get_payment_method() == 'vindi-pix' || $paymentMethod == 'pix') {
            $this->show_pix_template($order, $vindi_order, $order_to_iterate);
        }
    }

    private function show_pix_template($order, $vindi_order, $order_to_iterate)
    {
        if (!$order->is_paid() && !$order->has_status('cancelled')) {
            $this->vindi_settings->get_template(
                'pix-download.html.php',
                compact('vindi_order', 'order_to_iterate')
            );
        }
    }
}
