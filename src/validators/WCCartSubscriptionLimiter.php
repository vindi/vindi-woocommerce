<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

class WCCartSubscriptionLimiter
{
    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'limit_same_subscriptions'], 10, 3);
        add_filter('woocommerce_update_cart_validation', [$this, 'limit_duplicate_subscriptions_cart_update'], 10, 4);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'disallow_subscription_single_product_cart'], 10, 2);
        add_filter('woocommerce_checkout_fields', [$this, 'customize_billing_neighborhood_field']);
    }

    public function limit_same_subscriptions($passed, $product_id, $quantity)
    {
        $subscriptions_product = new WC_Subscriptions_Product();
        $product = wc_get_product($product_id);

        if ($product->is_virtual()) {
            return $passed;
        }

        if ($subscriptions_product->is_subscription($product_id)) {
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

    public function limit_duplicate_subscriptions_cart_update($passed, $_cart_item_key, $values, $quantity)
    {
        unset($_cart_item_key);
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
        $subscriptions_product = new WC_Subscriptions_Product();
        if ($subscriptions_product->is_subscription($product_id)) {
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

    public function disallow_subscription_single_product_cart($passed, $product_id)
    {
        $product = wc_get_product($product_id);

        if ($this->is_virtual_product($product)) {
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
        $subscriptions_product = new WC_Subscriptions_Product();
        if (empty($cart)) {
            return false;
        }

        $is_subscription = false;
        $new_product_subscription = $subscriptions_product->is_subscription($product_id);

        foreach ($cart as $cart_item) {
            if ($subscriptions_product->is_subscription($cart_item['data']->get_id())) {
                $is_subscription = true;
                break;
            }
        }

        return $is_subscription !== $new_product_subscription;
    }

    public function customize_billing_neighborhood_field($fields)
    {
        $fields['billing']['billing_neighborhood']['required'] = true;
        return $fields;
    }
}
