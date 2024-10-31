<?php

namespace VindiPaymentGateways;

use Exception;
use VindiPaymentGateways\GenerateUser;

class CheckoutGateways
{
    public function __construct()
    {
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_checkout_gateways'], 10, 1);
        add_action('woocommerce_pay_order_before_payment', [$this, 'add_billing_fields'], 10, 1);
        add_action('woocommerce_before_pay_action', [$this, 'save_billing_fields'], 10, 1);
    }

    public function filter_checkout_gateways($gateways)
    {
        $available = [
            'vindi-bank-slip',
            'vindi-bolepix',
            'vindi-credit-card',
            'vindi-pix',
        ];

        $hasSessionParams = false;
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        $gateway = filter_input(INPUT_GET, 'vindi-gateway') ?? false;

        $this->verify_session_params($hasSessionParams, $isPaymentLink, $gateway);

        if ($isPaymentLink) {
            $this->clear_session($hasSessionParams, $gateways);
            $gateways = $this->filter_available_gateways($gateways, $available);
            if ($gateway && isset($gateways[$gateway])) {
                return [$gateway => $gateways[$gateway]];
            }
        }
        return $gateways;
    }

    private function clear_session($hasSessionParams, $gateways)
    {
        if ($hasSessionParams && !$this->is_subscription_context()) {
            WC()->session->set('vindi-payment-link', '');
            WC()->session->set('vindi-gateway', '');
            return $gateways;
        }
    }

    private function is_subscription_context()
    {
        $cart = WC()->cart->get_cart();
        if (!empty($cart)) {
            return $this->has_subscription_item($cart);
        }
        return false;
    }

    private function has_subscription_item($cart)
    {
        foreach ($cart as $item) {
            if (isset($item['subscription_renewal']) || isset($item['subscription_initial_payment'])) {
                return true;
            }
        }
        return false;
    }

    private function verify_session_params(&$hasSessionParams, &$isPaymentLink, &$gateway)
    {
        if (!$isPaymentLink && WC()->session) {
            $isPaymentLink = WC()->session->get('vindi-payment-link');
            $gateway = WC()->session->get('vindi-gateway');
            $hasSessionParams = true;
        }
    }

    private function filter_available_gateways($gateways, $available)
    {
        $items = array_diff(array_keys($gateways), $available);
        foreach ($items as $item) {
            if (isset($gateways[$item])) {
                unset($gateways[$item]);
            }
        }
        return $gateways;
    }

    public function add_billing_fields()
    {
        $template_path = WP_PLUGIN_DIR . '/vindi-payment-gateway/src/templates/fields-order-pay-checkout.php';
        if (!file_exists($template_path)) {
            return;
        }

        $orderId = absint(get_query_var('order-pay'));

        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;

        if ($isPaymentLink) {
            $order = wc_get_order($orderId);
            $fields = WC()->checkout->get_checkout_fields('billing');
            $this->include_template_with_variables($template_path, compact('order', 'fields'));
        }
    }

    private function include_template_with_variables($template_path, $variables)
    {
        extract($variables);
        include $template_path;
    }

    public function save_billing_fields($order)
    {
        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        if (!$isPaymentLink) {
            return;
        }

        $fields = $this->get_billing_fields();
        try {
            $this->validate_required_fields();
            $this->update_billing_fields($order, $fields);
            $order->save();
        } catch (Exception $err) {
            wc_add_notice($err->getMessage(), 'error');
        }
    }

    private function get_billing_fields()
    {
        return [
            'first_name',
            'last_name',
            'persontype',
            'cpf',
            'company',
            'cnpj',
            'country',
            'postcode',
            'address_1',
            'address_2',
            'number',
            'neighborhood',
            'city',
            'state',
            'phone',
            'email'
        ];
    }

    private function update_billing_fields($order, $fields)
    {
        foreach ($fields as $key) {
            $field = filter_input(INPUT_POST, "billing_$key", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? false;
            if ($field) {
                $this->set_order_billing_field($order, $key, $field);
                $this->set_order_shipping_field($order, $key, $field);
            }
        }
    }

    private function set_order_billing_field($order, $key, $field)
    {
        $method = "set_billing_$key";
        if (method_exists($order, $method)) {
            $order->$method($field);
            return;
        }
        $order->update_meta_data("_billing_$key", $field);
    }

    private function set_order_shipping_field($order, $key, $field)
    {
        $method = "set_shipping_$key";
        if (method_exists($order, $method)) {
            $order->$method($field);
            return;
        }
        $order->update_meta_data("_shipping_$key", $field);
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
}
