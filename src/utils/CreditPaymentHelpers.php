<?php

namespace VindiPaymentGateways;

class CreditHelpers
{

    public function get_cart_total($cart)
    {
        $total = $cart->total;
        $recurring = end($cart->recurring_carts);
        if (floatval($cart->total) == 0 && is_object($recurring)) {
            $total = $recurring->total;
        }
        foreach ($cart->get_fees() as $fee) {
            if ($fee->name == __('Juros', VINDI)) {
                $total -= $fee->amount;
            }
        }
        return $total;
    }
}
