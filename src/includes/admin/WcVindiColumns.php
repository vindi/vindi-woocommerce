<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

class WcVindiColumns
{
    public function __construct()
    {
        add_filter('manage_edit-shop_order_columns', [$this, 'custom_shop_order_columns'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this, 'custom_shop_order_column_data'], 10, 1);
    }

    /**
     * Adds a new custom column on the orders page.
     *
     * @param array $columns The existing columns.
     * @return array  $new_columns The modified columns.
     */
    public function custom_shop_order_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_status' || $key === 'status') {
                $new_columns['vindi_payment_link'] = __('Link de Pagamento', 'vindi-payment-gateway');
            }
        }
        return $new_columns;
    }

    /**
     * Adds a new custom column on the orders page.
     *
     * @param array $columns The existing columns.
     */
    public function custom_shop_order_column_data($column)
    {
        global $post;
        $template_path = WP_PLUGIN_DIR . '/vindi-payment-gateway/src/templates/admin-payment-link-button-column.php';

        if (!$template_path) {
            return;
        }
        $order = wc_get_order($post->ID);

        if ($column === 'vindi_payment_link') {
            if (count($order->get_items()) > 0) {
                $status = $order->get_status();
                $gateway = $order->get_payment_method();
                $url_payment = $this->build_payment_link($order, $gateway);
                $post_type = $order->get_created_via();
                $has_sub = $this->has_subscription($order);
                $has_item = true;
                $variables = compact('url_payment', 'status', 'gateway', 'post_type', 'has_sub', 'has_item');
                $this->include_template_with_variables($template_path, $variables);
            }
        }
    }

    private function include_template_with_variables($template_path, $variables)
    {
        extract($variables);
        include $template_path;
    }

    /*
    * Build the payment link (Dummy function for illustration).
    * @param WC_Order $order The order object.
    * @param string $gateway The payment gateway.
    * @return string The payment link.
    */
    public function build_payment_link($order, $gateway)
    {
        $url = get_site_url();
        $gateway = $gateway ? "&vindi-gateway={$gateway}" : '';
        $orderId = $order->get_id();
        return "{$url}/wp-admin/post.php?post={$orderId}&action=edit&vindi-payment-link=true";
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
}
