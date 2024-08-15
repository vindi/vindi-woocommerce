<?php

namespace VindiPaymentGateways;

class OrderSetup
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'set_payment_gateway']);
        add_filter('woocommerce_payment_gateways', [$this, 'restrict_payment_gateways_for_vindi_payment_link']);

    }
    public function set_payment_gateway()
    {
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link', FILTER_VALIDATE_BOOLEAN) ?? false;
        $gateway = filter_input(INPUT_GET, 'vindi-gateway', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

        if ($isPaymentLink) {
            WC()->session->set('vindi-payment-link', $isPaymentLink);
        }

        if ($gateway) {
            WC()->session->set('vindi-gateway', $gateway);
        }
    }

    public function restrict_payment_gateways_for_vindi_payment_link($available_gateways)
    {
        if (is_admin() && isset($_GET['vindi-payment-link'])) {
            $allowed_gateways = [
                'vindi-bank-slip',
                'vindi-bolepix',
                'vindi-credit-card',
                'vindi-pix',
            ];

            foreach ($available_gateways as $gateway_id => $gateway) {
                if (!in_array($gateway_id, $allowed_gateways)) {
                    unset($available_gateways[$gateway_id]);
                }
            }
        }

        return $available_gateways;
    }
}
