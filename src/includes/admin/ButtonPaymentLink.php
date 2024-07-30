<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;


if (!defined('ABSPATH')) {
    exit;
}

class ButtonPaymentLink
{
    public function __construct()
    {
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'button_link_payment'], 20, 4);
    }

    public function button_link_payment($order)
    {
        $template_path = WP_PLUGIN_DIR . '/vindi-payment-gateway/src/templates/admin-payment-button.html.php';
        $has_item = false;
        $has_subscription = false;
        $order_status = $order->get_status();

        if (!$template_path) {
            return;
        }

        if (count($order->get_items()) > 0) {
            $order_type = get_post_type($order->get_id());
            $gateway = $order->get_payment_method();
            $has_subscription = $this->has_subscription($order);
            if ($order_type == 'shop_subscription') {
                $parent_order = $order->get_parent();
                if ($parent_order) {
                    $parent_order_id = $parent_order->get_id();
                    $order = wc_get_order($parent_order_id);
                    $has_subscription = true;
                }
            }

            if ($order->get_checkout_payment_url()) {
                $link_payment = $this->build_payment_link($order, $gateway);
            }
            $order_status = $order->get_status();
            $has_item = true;
            $urlAdmin = get_admin_url();
            $urlShopSubscription = "{$urlAdmin}edit.php?post_type=shop_subscription";
        }
        include $template_path;
    }

    public function has_subscription($order)
    {
        $order_items = $order->get_items();
        foreach ($order_items as $order_item) {
            if (WC_Subscriptions_Product::is_subscription($order_item->get_product_id()) && $order->get_created_via() == 'subscription') {
                return true;
            }
        }
        return false;
    }

    /*
    * Build the payment link (Dummy function for illustration).
    * @param WC_Order $order The order object.
    * @param string $gateway The payment gateway.
    * @return string The payment link.
    */
    public function build_payment_link($order, $gateway)
    {
        $url = wc_get_checkout_url();
        $gateway = $gateway ? "&vindi-gateway={$gateway}" : '';
        $orderId = $order->get_id();
        $orderKey = $order->get_order_key();

        return "{$url}order-pay/{$orderId}/?pay_for_order=true&key={$orderKey}&vindi-payment-link=true{$gateway}";
    }
}
