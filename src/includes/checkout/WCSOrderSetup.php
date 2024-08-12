<?php

namespace VindiPaymentGateways;

class OrderSetup
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'set_payment_gateway']);
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
}
