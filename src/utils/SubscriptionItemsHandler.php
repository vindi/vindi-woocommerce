<?php

class VindiSubscriptionItemsHandler
{
    /**
     * @var VindiSettings
     */
    private $vindi_settings;

    public function __construct(VindiSettings $vindi_settings)
    {
        $this->vindi_settings = $vindi_settings;
        $this->routes = $vindi_settings->routes;

        add_action('woocommerce_saved_order_items', array(&$this, 'synchronize_order_items'), 10, 2);
    }

    /**
     * TODO
     *
     * @param string $order_id
     * @param array $items
     *
     * @return null
     */
    public function synchronize_order_items($order_id, $items)
    {
        $order = wc_get_order($order_id);
        $vindi_subscription_id = $order->get_meta('vindi_subscription_id', true);
        if (!$vindi_subscription_id) {
            return;
        }

        $vindi_subscription = $this->routes->getSubscription($vindi_subscription_id);

        if (!$vindi_subscription
        || !array_key_exists('status', $vindi_subscription)
        || $vindi_subscription['status'] != 'active'
        ) {
            return;            
        }

        $vindi_subscription_product_items = $this->vindi_subscription_product_items($vindi_subscription);
        $wc_product_items = $this->wc_product_items($order);
        
        $this->vindi_settings->logger->log(var_dump($vindi_subscription_product_items));
        $this->vindi_settings->logger->log(var_dump($wc_product_items));
    }

    /**
     * @param array $vindi_subscription
     *
     * @return array
     */
    public function vindi_subscription_product_items($vindi_subscription)
    {
        $vindi_subscription_product_items = [];

        foreach ($vindi_subscription['product_items'] as $product_item) {
            $vindi_subscription_product_items[] = array(
                'product_item_id' => $product_item['id'],
                'quantity' => $product_item['quantity'],
                'product_id' => (string) $product_item['product']['id'],
                'price' => $product_item['pricing_schema']['price']
            );
        }

        return $vindi_subscription_product_items;
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    public function wc_product_items($order)
    {
        $wc_product_items = [];

        foreach ($order->get_items() as $order_item) {
            $wc_product_items[] = array(
                'quantity' => $order_item->get_quantity(),
                'product_id' => $order_item->get_product()->get_meta( 'vindi_product_id', true ),
                'price' => sprintf('%0.2f', round($order_item->get_subtotal() / $order_item->get_quantity(), 2))
            );
        }

        return $wc_product_items;
    }
}
