<?php

namespace VindiPaymentGateways;

class VindiSubscriptionStatusHandler
{
    /**
     * @var VindiSettings
     */
    private $vindi_settings;
    
    /**
     * @var VindiRoutes
     */
    private $routes;

    public function __construct(VindiSettings $vindi_settings)
    {
        $this->vindi_settings = $vindi_settings;
        $this->routes = $vindi_settings->routes;

        add_action('woocommerce_subscription_status_cancelled', array(
            &$this, 'cancelled_status',
        ));

        add_action('woocommerce_subscription_status_updated', array(
            &$this, 'filter_pre_status',
        ), 1, 3);

        add_action('woocommerce_order_fully_refunded', array(
            &$this, 'order_fully_refunded',
        ));

        add_action('woocommerce_order_status_cancelled', array(
            &$this, 'order_canceled',
        ));
    }

    /**
     * @param WC_Subscription $wc_subscription
     * @param string          $new_status
     * @param string          $old_status
     */
    public function filter_pre_status($wc_subscription, $new_status, $old_status)
    {
        switch ($new_status) {
            case 'on-hold':
                $this->suspend_status($wc_subscription);
                break;
            case 'active':
                $this->active_status($wc_subscription, $old_status);
                break;
            case 'cancelled':
                $this->cancelled_status($wc_subscription);
                break;
            case 'pending-cancel':
                if (!$this->vindi_settings->dependencies->is_wc_memberships_active()) {
                    $wc_subscription->update_status('cancelled');
                }
                break;
        }
    }

    /**
     * @param WC_Subscription $wc_subscription
     */
    public function suspend_status($wc_subscription)
    {
        $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
        if ($this->vindi_settings->get_synchronism_status()) {
            $this->routes->suspendSubscription($subscription_id);
        }
    }

    /**
     * @param WC_Subscription $wc_subscription
     */
    public function cancelled_status($wc_subscription)
    {
        $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
        if ($this->routes->isSubscriptionActive($subscription_id)) {
            $this->routes->suspendSubscription($subscription_id, true);
        }
    }

    /**
     * @param WC_Subscription $wc_subscription
     */
    public function active_status($wc_subscription, $old_status)
    {
        if ('pending' == $old_status) {
            return;
        }

        $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
        if ($this->vindi_settings->get_synchronism_status()
            && !$this->routes->isSubscriptionActive($subscription_id)) {
            $this->routes->activateSubscription($subscription_id);
        }
    }

    /**
     * @param WC_Subscription $wc_subscription
     */
    public function get_wc_subscription_id($subscription_id)
    {
        return get_post_meta($subscription_id, 'vindi_subscription_id', true) ? :
            get_post_meta($subscription_id, 'vindi_wc_subscription_id', true);
    }

    public function get_vindi_subscription_id($wc_subscription)
    {
        $subscription_id = method_exists($wc_subscription, 'get_id')
        ? $wc_subscription->get_id()
        : $wc_subscription->id;
        return $this->get_wc_subscription_id($subscription_id);
    }

    /**
     * @param WC_Order $order
     */
    public function order_fully_refunded($order)
    {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }

        if (wcs_order_contains_subscription($order, array('parent', 'renewal'))) {
            $subscriptions = wcs_get_subscriptions_for_order(wcs_get_objects_property($order, 'id'), array('order_type' => array('parent', 'renewal')));
            foreach ($subscriptions as $subscription) {
                $latest_order = $subscription->get_last_order();

                if (wcs_get_objects_property($order, 'id') == $latest_order && $subscription->can_be_updated_to('cancelled')) {
                    // translators: $1: opening link tag, $2: order number, $3: closing link tag
                    $subscription->update_status(
                        'cancelled',
                        wp_kses(sprintf(
                            __('A assinatura foi cancelada pelo pedido reembolsado %1$s#%2$s%3$s.', VINDI),
                            sprintf('<a href="%s">', esc_url(wcs_get_edit_post_link(wcs_get_objects_property($order, 'id')))),
                            $order->get_order_number(),
                            '</a>'
                        ), array('a' => array('href' => true))));
                }
            }
        }
    }

    /**
     * @param WC_Order $order
     */
    public function order_canceled($order)
    {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }

        $vindi_order = $order->get_meta('vindi_order', true);

        if (!is_array($vindi_order)) {
            return;
        }
        $single_payment_bill_id = 0;

        foreach ($vindi_order as $key => $item) {
            if (isset($vindi_order[$key]['bill']['status']) && $vindi_order[$key]['bill']['status'] !== 'canceled') {
                $single_payment_bill_id = $vindi_order[$key]['bill']['id'];
                $vindi_order[$key]['bill']['status'] = 'canceled';
            }
        }
        
        $order->update_meta_data('vindi_order', $vindi_order);
        $order->save();

        if ($single_payment_bill_id) {
            $this->routes->deleteBill($single_payment_bill_id);
        }
    }
}
