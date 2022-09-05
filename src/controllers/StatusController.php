<?php

/**
 * Create and register new status on WooCommerce Subscription
 *
 * @since 1.1.13
 *
 */

class StatusController
{
    function __construct()
    {
        add_filter('woocommerce_subscriptions_registered_statuses', array($this, 'register_subscription_waiting_status'), 100, 1);
        add_filter('wcs_subscription_statuses', array($this, 'add_subscription_waiting_status'), 100, 1);
        add_action('woocommerce_can_subscription_be_updated_to', array($this,'update_subscription_waiting_status'), 100, 3);
    }

    function register_subscription_waiting_status($subscription_statuses)
    {
        $status['wc-waiting'] = _nx_noop(
            'Waiting Payment <span class="count">(%s)</span>',
            'Waiting Payment <span class="count">(%s)</span>',
            'post status label including post count',
            'vindi-payment-gateway'
        );

        $subscription_statuses = array_merge($status, $subscription_statuses);
        return $subscription_statuses;
    }

    function add_subscription_waiting_status($subscription_statuses)
    {
        $status['wc-waiting'] =  _x('Waiting Payment', 'vindi-payment-gateway');

        $subscription_statuses = array_merge($status, $subscription_statuses);
        return $subscription_statuses;
    }

    function update_subscription_waiting_status($can_be_updated, $new_status, $subscription)
    {
        if ( $new_status == 'waiting' ) {
            if ( $subscription->payment_method_supports( 'subscription_suspension' ) && $subscription->has_status( array( 'active', 'pending', 'on-hold', 'cancelled' ) ) ) {
                $can_be_updated = true;
            } else {
                $can_be_updated = false;
            }
        }
        
        return $can_be_updated;
    }
}
