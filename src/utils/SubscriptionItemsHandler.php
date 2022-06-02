<?php

class VindiSubscriptionItemsHandler
{
    /**
     * @var string
     */
    private $vindi_subscription_id;

    public function __construct(VindiSettings $vindi_settings)
    {
        $this->routes = $vindi_settings->routes;

        add_action(
            'woocommerce_saved_order_items', array(&$this, 'saved_order_items_hook_handler'),
            10, 2
        );
        add_action(
            'woocommerce_order_after_calculate_totals', array(&$this, 'after_calculate_totals_hook_handler'),
            10, 2
        );
    }

    /**
     * @param string $order_id
     * @param array $items
     */
    public function saved_order_items_hook_handler($order_id, $items)
    {
        $order = wc_get_order($order_id);

        if (is_a($order, 'WC_Subscription')) {
            $this->synchronize_order_items($order);
        }
    }

    /**
     * @param array $and_taxes
     * @param WC_Subscription $order
     */
    public function after_calculate_totals_hook_handler($and_taxes, $order)
    {
        if (! is_admin() && is_a($order, 'WC_Subscription')) {
            $this->synchronize_order_items($order);
        }
    }

    /**
     * @param WC_Subscription $order
     */
    public function synchronize_order_items($order)
    {
        $this->vindi_subscription_id = $order->get_meta('vindi_subscription_id', true);
        if (!$this->vindi_subscription_id) {
            return;
        }

        $vindi_subscription = $this->routes->getSubscription($this->vindi_subscription_id);

        if (!$vindi_subscription
        || !array_key_exists('status', $vindi_subscription)
        || $vindi_subscription['status'] != 'active'
        ) {
            return;            
        }

        $vindi_subscription_product_items = $this->vindi_subscription_product_items($vindi_subscription);
        $wc_product_items = $this->wc_product_items($order);
        
        $this->check_product_items($vindi_subscription_product_items, $wc_product_items);
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
            $vindi_subscription_product_items[(string) $product_item['product']['id']] = array(
                'product_item_id' => $product_item['id'],
                'quantity' => $product_item['quantity'],
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
            $vindi_product_id = $order_item->get_product()->get_meta('vindi_product_id', true);

            if (!$vindi_product_id) {
                continue;
            }

            $wc_product_items[$vindi_product_id] = array(
                'quantity' => $order_item->get_quantity(),
                'price' => sprintf('%0.2f', round($order_item->get_subtotal() / $order_item->get_quantity(), 2))
            );
        }

        return $wc_product_items;
    }

    /**
     * @param array $vindi_subscription_product_items
     * @param array $wc_product_items
     */
    public function check_product_items($vindi_subscription_product_items, $wc_product_items)
    {
        foreach ($wc_product_items as $key => $value) {
            if (array_key_exists($key, $vindi_subscription_product_items)) {
                if ($value['quantity'] != $vindi_subscription_product_items[$key]['quantity']
                    || $value['price'] != $vindi_subscription_product_items[$key]['price']) {
                    $this->update_product_item($vindi_subscription_product_items[$key]['product_item_id'], $value);
                }

                // removes already updated product items
                unset($vindi_subscription_product_items[$key]);
            } else {
                $this->insert_product_item($key, $value);
            }
        }

        // removes underlying vindi product items
        $this->remove_underlying_product_items($vindi_subscription_product_items);

    }

    /**
     * @param string $product_item_id
     * @param array $params
     */
    public function update_product_item($product_item_id, $params)
    {
        $data = array(
            'quantity' => $params['quantity'],
            'pricing_schema' => array(
                'price' => $params['price'],
                'schema_type' => 'per_unit'
            )
        );

        $this->routes->updateSubscriptionProductItem($product_item_id, $data);
    }

    /**
     * @param string $product_id
     * @param array $params
     */
    public function insert_product_item($product_id, $params)
    {
        $data = array(
            'product_id' => $product_id,
            'subscription_id' => $this->vindi_subscription_id,
            'quantity' => $params['quantity'],
            'pricing_schema' => array(
                'price' => $params['price'],
                'schema_type' => 'per_unit'
            )
        );

        $this->routes->createSubscriptionProductItem($data);
    }

    /**
     * @param array $underlying_product_items
     */
    public function remove_underlying_product_items($underlying_product_items)
    {
        if (!empty($underlying_product_items)) {
            foreach ($underlying_product_items as $product_item) {
                $this->routes->deleteProductItem($product_item['product_item_id']);
            }
        }
    }
}
