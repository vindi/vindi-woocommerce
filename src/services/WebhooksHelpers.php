<?php

namespace VindiPaymentGateways;

use WC_Order;
use Exception;

class WebhooksHelpers
{
    private $vindiWebhooks;

    public function __construct(VindiWebhooks $vindiWebhooks)
    {
        $this->vindiWebhooks = $vindiWebhooks;
    }

    public function handle_subscription_renewal($renewInfos, $data)
    {
        $vindiId = $renewInfos['vindi_subscription_id'];
        $cycle = $renewInfos['cycle'];
        $hasOrder = $this->vindiWebhooks->subscription_has_order_in_cycle($vindiId, $cycle);
        if (!$hasOrder) {
            $this->vindiWebhooks->subscription_renew($renewInfos);
            $this->vindiWebhooks->update_next_payment($data);
            return true;
        }
        return false;
    }

    public function handle_trial_period($subscriptionId)
    {
        $cleanSubscriptionId = $this->vindiWebhooks->find_subscription_by_id($subscriptionId);
        $subscription = wcs_get_subscription($cleanSubscriptionId);
        if ($subscription->get_trial_period() > 0 && $subscription->get_status() == "active") {
            $parentId = $subscription->get_parent_id();
            $order = new WC_Order($parentId);
            $order->update_status('pending', 'Período de teste vencido');
            $subscription->update_status('on-hold');
            return true;
        }
        return false;
    }
}
