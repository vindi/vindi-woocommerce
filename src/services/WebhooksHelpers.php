<?php

namespace VindiPaymentGateways;

use DateTime;
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
        $now = new DateTime();
        $endTrial = new DateTime();
        $endTrial->setTimestamp($subscription->get_time('trial_end'));
        if ($endTrial > $now && $subscription->get_status() == "active") {
            $parentId = $subscription->get_parent_id();
            $order = new WC_Order($parentId);
            $order->update_status('pending', 'Período de teste vencido');
            $subscription->update_status('on-hold');
            return true;
        }
        return false;
    }

    public function renew_infos_array($data)
    {
        $charge = $data->bill->charges[0];
        return [
          'wc_subscription_id' => $data->bill->subscription->code,
          'vindi_subscription_id' => $data->bill->subscription->id,
          'plan_name' => str_replace('[WC] ', '', $data->bill->subscription->plan->name),
          'cycle' => $data->bill->period->cycle,
          'bill_status' => $data->bill->status,
          'bill_id' => $data->bill->id,
          'bill_print_url' => $charge->print_url,
          'charge_id' => $charge->id,
          'payment_method' => $charge->payment_method->code,
          'vindi_url' => $data->bill->url,
          'pix_expiration' => $charge->last_transaction->gateway_response_fields->max_days_to_keep_waiting_payment,
          'pix_code' => $charge->last_transaction->gateway_response_fields->qrcode_original_path,
          'pix_qr' => $charge->last_transaction->gateway_response_fields->qrcode_path,
        ];
    }

    public function make_array_bill($renew_infos)
    {
        return array(
          'id' => $renew_infos['bill_id'],
          'status' => $renew_infos['bill_status'],
          'bank_slip_url' => $renew_infos['bill_print_url'],
          'charge_id' => $renew_infos['charge_id'],
          'vindi_url' => $renew_infos['vindi_url'],
          'payment_method' => $renew_infos['payment_method'],
          'pix_expiration' =>$renew_infos['pix_expiration'],
          'pix_code' => $renew_infos['pix_code'],
          'pix_qr' =>$renew_infos['pix_qr']
        );
    }
}
