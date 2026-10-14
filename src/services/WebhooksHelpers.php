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
            $mirrored_trigger = $subscription->get_meta('vindi_billing_trigger_type', true);
            if ('end_of_period' === $mirrored_trigger) {
                return false;
            }

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
        $gateway_fields = isset($charge->last_transaction->gateway_response_fields)
            ? $charge->last_transaction->gateway_response_fields
            : null;
        $payment_company = isset($charge->payment_company) ? $charge->payment_company : null;

        $brand = '';
        if (!empty($payment_company->code)) {
            $brand = (string) $payment_company->code;
        } elseif (!empty($payment_company->name)) {
            $brand = (string) $payment_company->name;
        } elseif (!empty($gateway_fields->brand)) {
            $brand = (string) $gateway_fields->brand;
        }

        $card_last_four = '';
        if (!empty($gateway_fields->card_number_last_four)) {
            $card_last_four = (string) $gateway_fields->card_number_last_four;
        } elseif (!empty($gateway_fields->card_number)) {
            $card_digits = preg_replace('/\D/', '', (string) $gateway_fields->card_number);
            if (strlen($card_digits) >= 4) {
                $card_last_four = substr($card_digits, -4);
            }
        }

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
          'payment_company' => $brand,
          'brand' => $brand,
          'card_last_four' => $card_last_four,
          'installments' => isset($charge->installments) ? (int) $charge->installments : null,
          'vindi_url' => $data->bill->url,
          'pix_expiration' => $gateway_fields->max_days_to_keep_waiting_payment ?? null,
          'pix_code' => $gateway_fields->qrcode_original_path ?? null,
          'pix_qr' => $gateway_fields->qrcode_path ?? null,
        ];
    }

    public function make_array_bill($renew_infos)
    {
        $bill = array(
          'id' => $renew_infos['bill_id'],
          'status' => $renew_infos['bill_status'],
          'bank_slip_url' => $renew_infos['bill_print_url'],
          'charge_id' => $renew_infos['charge_id'],
          'vindi_url' => $renew_infos['vindi_url'],
          'payment_method' => $renew_infos['payment_method'],
          'pix_expiration' => $renew_infos['pix_expiration'] ?? null,
          'pix_code' => $renew_infos['pix_code'] ?? null,
          'pix_qr' => $renew_infos['pix_qr'] ?? null,
        );

        if (!empty($renew_infos['brand'])) {
            $bill['brand'] = (string) $renew_infos['brand'];
            $bill['payment_company'] = (string) $renew_infos['brand'];
        }
        if (!empty($renew_infos['card_last_four'])) {
            $bill['card_last_four'] = (string) $renew_infos['card_last_four'];
        }
        if (!empty($renew_infos['installments'])) {
            $bill['installments'] = (int) $renew_infos['installments'];
        }

        return $bill;
    }
}
