<?php

namespace VindiPaymentGateways;

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
            if ($key === 'order_status' || $key === 'Status') {
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
        $template_path = WP_PLUGIN_DIR . '/vindi-payment-gateway/src/templates/admin-payment-link-button.php';

        if (!$template_path) {
            return;
        }

        if ($column === 'vindi_payment_link') {
            $order = wc_get_order($post->ID);
            if (count($order->get_items()) > 0) {
                $order_status = $order->get_status();
                $gateway = $order->get_payment_method();
                $link_payment = $this->build_payment_link($order, $gateway);
                $variables = compact('link_payment', 'order_status', 'order', 'gateway');
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
        $url = wc_get_checkout_url();
        $gateway = $gateway ? "&vindi-gateway={$gateway}" : '';
        $orderId = $order->get_id();
        $orderKey = $order->get_order_key();

        return "{$url}order-pay/{$orderId}/?pay_for_order=true&key={$orderKey}&vindi-payment-link=true{$gateway}";
    }
}
