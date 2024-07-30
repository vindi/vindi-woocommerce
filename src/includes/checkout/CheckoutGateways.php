<?php

namespace VindiPaymentGateways;

use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutGateways
{
    public function __construct()
    {
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_checkout_gateways'], 10, 1);
        add_action('woocommerce_pay_order_before_payment', [$this, 'add_billing_fields'], 10, 1);
        add_action('woocommerce_before_pay_action', [$this, 'save_billing_fields'], 10, 1);
    }

    private function auto_create_user_for_order($order)
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

    public function filter_checkout_gateways($gateways)
    {
        $available = [
            'vindi-bank-slip',
            'vindi-bolepix',
            'vindi-credit-card',
            'vindi-pix',
        ];

        $isPaymentLink = $this->get_payment_link_status();
        $gateway = $this->get_gateway();

        if ($isPaymentLink) {
            $gateways = $this->filter_available_gateways($gateways, $available);
            if ($gateway && isset($gateways[$gateway])) {
                return [$gateway => $gateways[$gateway]];
            }
        }
        return $gateways;
    }

    private function get_payment_link_status()
    {
        $isPaymentLink = '';

        if (WC()->session) {
            $isPaymentLink = WC()->session->get('vindi-payment-link');
        }

        if (!$isPaymentLink) {
            $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;
        }

        return $isPaymentLink;
    }

    private function get_gateway()
    {
        $gateway = '';

        if (WC()->session) {
            $gateway = WC()->session->get('vindi-gateway');
        }

        if (!$gateway) {
            $gateway = filter_input(INPUT_GET, 'vindi-gateway') ?? false;
        }

        return $gateway;
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
        if (!$template_path) {
            return;
        }

        $orderId = absint(get_query_var('order-pay'));

        $isPaymentLink = filter_input(INPUT_GET, 'vindi-payment-link') ?? false;

        if ($isPaymentLink) {
            $order = wc_get_order($orderId);
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

        $fields = $this->get_billing_fields();

        try {
            $this->validate_required_fields();
            $this->update_billing_fields($order, $fields);
            $order->save();
            $this->auto_create_user_for_order($order);
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
            'number',
            'address_2',
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
        if (method_exists($order, "set_billing_$key")) {
            $order->{"set_billing_$key"}($field);
        } else {
            $order->update_meta_data("_billing_$key", $field);
        }
    }

    private function set_order_shipping_field($order, $key, $field)
    {
        if (method_exists($order, "set_shipping_$key")) {
            $order->{"set_shipping_$key"}($field);
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
}
