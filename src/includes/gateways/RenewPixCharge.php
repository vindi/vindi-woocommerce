<?php

namespace VindiPaymentGateways;

class RenewPixCharge
{
    public function __construct()
    {
        add_action('wp_ajax_renew_pix_charge', [$this, 'renew_pix_charge']);
        add_action('wp_ajax_nopriv_renew_pix_charge', [$this, 'renew_pix_charge']);
    }

    public function renew_pix_charge()
    {
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $charge_id = filter_input(INPUT_POST, 'charge_id', FILTER_SANITIZE_NUMBER_INT);
        $subscription_id = filter_input(INPUT_POST, 'subscription_id', FILTER_SANITIZE_NUMBER_INT);

        $order = wc_get_order($order_id);
        $vindi_order = $order->get_meta('vindi_order', true);

        if ($charge_id && $subscription_id) {
            $routes = new VindiRoutes($this->settings);
            $charge = $routes->renewCharge($charge_id);

            if (isset($charge['status']) && isset($charge['last_transaction']['gateway_response_fields'])) {
                $subscription = $vindi_order[$subscription_id];
                $bill = $this->create_bill_array($subscription, $charge);
                $vindi_order[$subscription_id]['bill'] = $bill;
                $order->update_meta_data('vindi_order', $vindi_order);
                $order->save();
            }
        }
    }

    public function create_bill_array($subscription, $charge)
    {
        $last_transaction = $charge['last_transaction']['gateway_response_fields'];
        $payment_method = $charge['last_transaction']['payment_method'];
        $bill = [
            'id' => $subscription['bill']['id'],
            'status' => $subscription['bill']['status'],
            'charge_id' => $charge['id'],
            'vindi_url' => $subscription['bill']['url'],
            'payment_method' =>  $payment_method['code'],
            'pix_expiration' => $last_transaction['max_days_to_keep_waiting_payment'],
            'pix_code' => $last_transaction['qrcode_original_path'],
            'pix_qr' => $last_transaction['qrcode_path'],
        ];

        return $bill;
    }
}
