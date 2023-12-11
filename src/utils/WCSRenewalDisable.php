<?php

namespace VindiPaymentGateways;

class VindiWCSRenewalDisable
{
    public function __construct()
    {
        // Hook as early as possible to try disabling WC_Subcriptions_Manager handling
        add_action('wp_loaded', [$this, 'hook_before_prepare_renewal'], 1);
    }

    public function hook_before_prepare_renewal()
    {
        if (class_exists('WC_Subscriptions_Manager', false)) {
            add_action('woocommerce_scheduled_subscription_payment', [
              $this,
              'deactivate_renewal_prepare'
            ], 0, 1);
        }
    }

    public function deactivate_renewal_prepare($subscription_id)
    {
        $subscription = wcs_get_subscription($subscription_id);

        // Check if this subscriptions is a Vindi Subscription
        if (empty($subscription->get_meta('vindi_wc_subscription_id'))
            && empty($subscription->get_meta('vindi_subscription_id'))) {
            return;
        }

        // Disable Woocommerce Subscriptions Renewal order and let Vindi handle it via webhooks
        remove_action(
            'woocommerce_scheduled_subscription_payment',
            'WC_Subscriptions_Manager::prepare_renewal',
            1
        );
    }
}
