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
        return null;
    }
}
