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
            $item = $order_data['has_item'];
            $single =  $order_data['has_single_product'];
            $sub = $order_data['has_subscription'];
            $status = $order_data['order_status'];
            $link = $order_data['link_payment'];
            $shop =  $order_data['urlShopSubscription'];
            $type = get_post_type($order->get_id());
            $created = $order->get_created_via();
            $parent = $order_data['parent'];
            $disable = $this->should_disable($created, $sub, $order, $order_data);
            $hasClient = $order->get_customer_id();
            $order_info = compact('type', 'created', 'parent', 'disable', 'hasClient');
            $variables = compact('item', 'sub', 'status', 'link', 'shop', 'single');
            $this->include_template_with_variables($template_path, $variables, $order_info);
        }
    }

    private function should_disable($created, $has_sub, $order, $order_data)
    {
        $posttype = get_post_type();
        $hasClient = $order->get_customer_id();
        $has_item = $order_data['has_item'];

        if (!$hasClient || !$has_item) {
            return false;
        }

        if ($posttype == 'shop_order') {
            return $this->evaluate_shop_order($has_sub, $created);
        }

        if ($posttype == 'shop_subscription') {
            return $this->evaluate_shop_subscription($has_sub, $created, $order_data);
        }

        return false;
    }

    private function evaluate_shop_order($has_sub, $created)
    {
        if ($has_sub && $created == "admin") {
            return false;
        }
        return true;
    }

    private function evaluate_shop_subscription($has_sub, $created, $order_data)
    {
        if ($has_sub && $created == "admin" && !$order_data['has_single_product']) {
            return true;
        }
        return false;
    }

    private function get_order_data($order)
    {
        $order_data = [
            'has_item' => false,
            'has_subscription' => false,
            'has_single_product' => false,
            'order_status' => $order->get_status(),
            'link_payment' => null,
            'urlAdmin' => get_admin_url(),
            'urlShopSubscription' => null,
            'parent' => false
        ];
        if (count($order->get_items()) > 0) {
            $order_data['has_subscription'] = $this->has_subscription($order);
            $order_data['has_single_product'] = $this->has_single_product($order);

            if ($order->get_checkout_payment_url()) {
                $order_data['link_payment'] = $this->build_payment_link($order, $order->get_payment_method());
            }
            $order_data = $this->handle_shop_subscription($order, $order_data);
            $order_data['order_status'] = $order->get_status();
            $order_data['has_item'] = true;
            $order_data['urlShopSubscription'] = "{$order_data['urlAdmin']}post-new.php?post_type=shop_subscription";
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
                $order_data['link_payment'] = $this->build_payment_link($order, $order->get_payment_method());
                $order_data['has_subscription'] = true;
                $order_data['parent'] = true;
            }
        }

        return $order_data;
    }

    public function has_subscription($order)
    {
        $subscriptions_product = new WC_Subscriptions_Product();
        $order_items = $order->get_items();
        foreach ($order_items as $order_item) {
            if ($subscriptions_product->is_subscription($order_item->get_product_id())) {
                return true;
            }
        }
        return false;
    }

    private function has_single_product($order)
    {
        $subscriptions_product = new WC_Subscriptions_Product();
        $order_items = $order->get_items();
        foreach ($order_items as $order_item) {
            if (!$subscriptions_product->is_subscription($order_item->get_product_id())) {
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
        $is_renewal = get_post_meta($order->get_id(), '_subscription_renewal', true);

        if ($is_renewal) {
            $url = get_site_url();
            return "{$url}/my-account/view-order/{$orderId}";
        }

        return "{$url}order-pay/{$orderId}/?pay_for_order=true&key={$orderKey}&vindi-payment-link=true{$gateway}";
    }

    private function include_template_with_variables($template_path, $variables, $order_info)
    {
        extract(array_merge($variables, $order_info));
        include $template_path;
    }
}
