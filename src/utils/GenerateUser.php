<?php

namespace VindiPaymentGateways;

use WC_Customer;

class GenerateUser
{
    public function auto_create_user_for_order($order)
    {
        $billing_email = $order->get_billing_email();
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();

        $user_id = $this->get_or_create_user($billing_email, $billing_first_name, $billing_last_name);
        $this->set_customer_data($user_id, $order);

        if ($user_id) {
            $order->set_customer_id($user_id);
            $order->save();
        }
    }

    private function get_or_create_user($billing_email, $billing_first_name, $billing_last_name)
    {
        $user_id = $this->set_user_id($billing_email);
        if ($user_id) {
            return $user_id;
        }

        $username = $this->generate_unique_username($billing_first_name, $billing_last_name, $billing_email);
        $password = wp_generate_password(12, false);
        $user_id = wp_create_user($username, $password, $billing_email);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        return $user_id;
    }

    private function set_customer_data($customer_id, $order)
    {
        $customer = new WC_Customer($customer_id);
    
        $billing_address_2 = $order->get_billing_address_2();
        $billing_neighborhood = get_post_meta($order->get_id(), '_billing_neighborhood', true);
        $full_address_2 = '';

        if (!empty($billing_address_2) && !empty($billing_neighborhood)) {
            $full_address_2 = $billing_address_2 . ' - ' . $billing_neighborhood;
        } elseif (!empty($billing_address_2)) {
            $full_address_2 = $billing_address_2;
        } elseif (!empty($billing_neighborhood)) {
            $full_address_2 = $billing_neighborhood;
        }
    
        $customer->set_billing_country($order->get_billing_country());
        $customer->set_billing_postcode($order->get_billing_postcode());
        $customer->set_billing_address_1($order->get_billing_address_1());
        $customer->set_billing_address_2($full_address_2);
        $customer->set_billing_city($order->get_billing_city());
        $customer->set_billing_state($order->get_billing_state());
        $customer->set_billing_phone($order->get_billing_phone());
        $customer->save();
    }

    private function set_user_id($userEmail)
    {
        $user = get_user_by('email', $userEmail);
        if ($user) {
            return $user->ID;
        }
        return null;
    }

    private function generate_unique_username($billing_first_name, $billing_last_name, $billing_email)
    {
        $username = strtolower($billing_first_name . '_' . $billing_last_name);
        $username = str_replace(' ', '_', $username);
        $suffix = 1;

        while (username_exists($username)) {
            $username = strtolower($billing_first_name . '_' . $billing_last_name . '_' . $suffix);
            $suffix++;
        }

        if (!isset($username) || empty($username)) {
            $username = current(explode('@', $billing_email));
        }

        return $username;
    }
}
