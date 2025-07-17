<?php

namespace VindiPaymentGateways;

/**
 * WC_Meta_Box_Coupon_Data Class updated with custom fields.
 */
class ProductsMetabox
{
    public function __construct()
    {
        add_action('woocommerce_variation_options', [
            $this,
            'woocommerce_variable_subscription_custom_fields'
        ], 10, 3);

        add_action('woocommerce_product_options_general_product_data', [
            $this,
            'woocommerce_subscription_custom_fields'
        ]);

        add_action('save_post', [
            $this,
            'filter_woocommerce_product_custom_fields'
        ]);

        add_action('woocommerce_save_product_variation', [
            $this,
            'handle_saving_variable_subscription'
        ], 10, 1);
    }

    public function woocommerce_subscription_custom_fields()
    {
        global $woocommerce, $post;
        $product = wc_get_product($post->ID);
        if (!$product) {
            return;
        }

        if ($product->is_type('subscription') || $post->post_status === 'auto-draft') {
            if ($this->check_credit_payment_active($woocommerce)) {
                $this->show_meta_custom_data($post->ID);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function woocommerce_variable_subscription_custom_fields($loop, $variation_data, $variation)
    {
        global $woocommerce;
        $product = wc_get_product($variation->ID);
        if (!$product) {
            return;
        }

        if ($product->is_type('subscription_variation') || $variation->post_status === 'auto-draft') {
            if ($this->check_credit_payment_active($woocommerce)) {
                $this->show_meta_custom_data($variation->ID);
            }
        }
    }

    private function show_meta_custom_data($subscription_id)
    {
        $product = wc_get_product($subscription_id);
        $field_id = "vindi_max_credit_installments_$subscription_id";
        $value = $product ? $product->get_meta($field_id, true) : '';

        echo '<div class="product_custom_field">';

        woocommerce_wp_text_input(
            array(
                'id'    => $field_id,
                'value' => $value,
                'label' => __('Máximo de parcelas com cartão de crédito', 'woocommerce'),
                'type'  => 'number',
                'description' => 'Esse campo controla a quantidade máxima de parcelas
                    para compras com cartão de crédito.',
                "desc_tip"    => true,
                'custom_attributes' => array(
                    'max' => '12',
                    'min' => '1'
                )
            )
        );

        echo '</div>';
    }

    public function filter_woocommerce_product_custom_fields($post_id)
    {
        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }

        if ($product->is_type('subscription')) {
            $this->handle_saving_simple_subscription($product);
        }
    }

    public function handle_saving_variable_subscription($variation)
    {
        $periods = $this->get_post_vars('variable_subscription_period', false);
        $intervals = $this->get_post_vars('variable_subscription_period_interval', false);
        $installments = $this->get_post_vars("vindi_max_credit_installments_$variation");

        $this->save_woocommerce_product_custom_fields(
            $variation,
            $installments,
            end($periods),
            end($intervals)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function get_post_vars($var, $filter = true)
    {
        if (!empty($_POST) && isset($_POST[$var])) {
            if (!$filter) {
                return $_POST[$var];
            }

            return filter_var($_POST[$var]);
        }

        return false;
    }

    private function handle_saving_simple_subscription($product)
    {
        $post_id = $product->get_id();

        $period = $product->get_meta('_subscription_period', true);
        $interval = $product->get_meta('_subscription_period_interval', true);
        $installments = $this->get_post_vars("vindi_max_credit_installments_$post_id");
        if ($period && $interval) {
            $this->save_woocommerce_product_custom_fields($post_id, $installments, $period, $interval);
        }
    }

    private function save_woocommerce_product_custom_fields($post_id, $installments, $period, $interval)
    {
        $product = wc_get_product($post_id);
        if ($period === 'year' && $installments > 12) {
            $installments = 12;
        }
        error_log(var_export("interval: $interval, period: $period, installments: $installments", true));
        if ($period === 'month' && $installments > $interval) {
            $installments = $interval;
        }
        if (!$installments) {
            $installments = 1;
        }
        $product->update_meta_data("vindi_max_credit_installments_$post_id", $installments);
        $product->save();
    }

    private function check_credit_payment_active($woocommerce)
    {
        $gateways = array_keys($woocommerce->payment_gateways->get_available_payment_gateways());
        foreach ($gateways as $key) {
            if ($key === 'vindi-credit-card') {
                return true;
            }
        }

        return false;
    }
}
