<?php

namespace VindiPaymentGateways;

class OrderActionsRemover
{
    public function __construct()
    {
        add_filter('woocommerce_my_account_my_orders_actions', [$this,'customize_order_actions'], 10, 2);
    }

    public function customize_order_actions($actions, $order)
    {
        if ($order->has_status(array('pending', 'on-hold')) && $order->get_meta('vindi_order', true)) {
            unset($actions['pay']);
            return $actions;
        }
        return $actions;
    }
}
