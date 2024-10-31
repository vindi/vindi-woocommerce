<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

class FilterCartNeedsPayment
{
    public function __construct()
    {
        add_filter('woocommerce_cart_needs_payment', [$this, 'filter_woocommerce_cart_needs_payment'], 10, 2);
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
        $subscriptions_product = new WC_Subscriptions_Product();
        $items = $cart->get_cart();
        foreach ($items as $item) {
            if (class_exists('WC_Subscriptions_Product')
                && $subscriptions_product->get_trial_length($item['product_id']) > 0
            ) {
                return true;
            }
        }

        return false;
    }
}
