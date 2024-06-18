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

    public function handleSubscriptionRenewal($renewInfos, $data)
    {
        if (!$this->vindiWebhooks->subscriptionHasOrderInCycle($renewInfos['vindi_subscription_id'], $renewInfos['cycle'])) {
            $this->vindiWebhooks->subscriptionRenew($renewInfos);
            $this->vindiWebhooks->updateNextPayment($data);
            return true;
        }
        return false;
    }

    public function handleTrialPeriod($subscriptionId)
    {
        $cleanSubscriptionId = $this->vindiWebhooks->findSubscriptionById($subscriptionId);
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
