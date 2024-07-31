<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

class ButtonPaymentLink
{
    public function __construct()
    {
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'button_link_payment'], 20, 4);
    }

    public function button_link_payment($order)
    {
        $template_path = WP_PLUGIN_DIR . '/vindi-payment-gateway/src/templates/admin-payment-button.html.php';

        if (!$template_path) {
            return;
        }

        $order_data = $this->get_order_data($order);

        if ($order) {
            $has_item = $order_data['has_item'];
            $has_sub = $order_data['has_subscription'];
            $orde_status = $order_data['order_status'];
            $link_payment = $order_data['link_payment'];
            $urlAdmin =  $order_data['urlAdmin'];
            $urlShopSubscription =  $order_data['urlShopSubscription'];
            $variables = compact('has_item', 'has_sub', 'orde_status', 'link_payment', 'urlAdmin', 'urlShopSubscription');
            $this->include_template_with_variables($template_path, $variables);
        }
    }

    private function get_order_data($order)
    {
        $order_data = [
            'has_item' => false,
            'has_subscription' => false,
            'order_status' => $order->get_status(),
            'link_payment' => null,
            'urlAdmin' => get_admin_url(),
            'urlShopSubscription' => null
        ];
        if (count($order->get_items()) > 0) {
            $order_data['has_subscription'] = $this->has_subscription($order);
            $order_data = $this->handle_shop_subscription($order, $order_data);
            if ($order->get_checkout_payment_url()) {
                $order_data['link_payment'] = $this->build_payment_link($order, $order->get_payment_method());
            }
            $order_data['order_status'] = $order->get_status();
            $order_data['has_item'] = true;
            $order_data['urlShopSubscription'] = "{$order_data['urlAdmin']}edit.php?post_type=shop_subscription";
        }

        return $order_data;
    }

    private function handle_shop_subscription($order, $order_data)
    {
        if (get_post_type($order->get_id()) == 'shop_subscription') {
            $parent_order = $order->get_parent();
            if ($parent_order) {
                $parent_order_id = $parent_order->get_id();
                $order = wc_get_order($parent_order_id);
                $order_data['has_subscription'] = true;
            }
        }

        return $order_data;
    }

    public function has_subscription($order)
    {
        $subscriptions_product = new WC_Subscriptions_Product();
        $order_items = $order->get_items();
        foreach ($order_items as $order_item) {
            if ($subscriptions_product->is_subscription($order_item->get_product_id())
            && $order->get_created_via() == 'subscription') {
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

    private function include_template_with_variables($template_path, $variables)
    {
        extract($variables);
        include $template_path;
    }
}
