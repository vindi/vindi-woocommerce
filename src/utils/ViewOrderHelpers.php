<?php

namespace VindiPaymentGateways;

class VindiViewOrderHelpers
{

    public function clean_order_data($order)
    {
        $filtered_order = array_filter($order, function ($value) {
            return !empty($value) && is_array($value);
        });

        return $filtered_order;
    }

    public function check_payment_methods($payment_methods)
    {
        return $payment_methods === false || empty($payment_methods) || !count($payment_methods['credit_card']);
    }
}
