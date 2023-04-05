<?php
namespace VindiPaymentGateways;

/**
 * WC_Meta_Box_Coupon_Data Class updated with custom fields.
 */
class ProductsMetabox
{
    public function __construct()
    {
        add_action('woocommerce_product_after_variable_attributes', [
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
    }

    public function woocommerce_subscription_custom_fields()
    {
        global $woocommerce, $post;

        $product = wc_get_product($post->ID);
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
        if ($product->is_type('subscription_variation') || $variation->post_status === 'auto-draft') {
            if ($this->check_credit_payment_active($woocommerce)) {
                $this->show_meta_custom_data($variation->ID);
            }
        }
    }

    private function show_meta_custom_data($subscription_id)
    {
        echo '<div class="product_custom_field">';

        woocommerce_wp_text_input(
            array(
                'id'    => "vindi_max_credit_installments_$subscription_id",
                'value' => get_post_meta($subscription_id, "vindi_max_credit_installments_$subscription_id", true),
                'label' => __('Máximo de parcelas com cartão de crédito', 'woocommerce'),
                'type'  => 'number',
                'description' => sprintf(
                    'Esse campo controla a quantidade máxima de parcelas 
                    para compras com cartão de crédito. <strong> %s </strong>',
                    '(Somente para assinaturas anuais!)'
                ),
                "desc_tip"    => true,
                'custom_attributes' => array(
                    'max' => '12',
                    'min' => '0'
                )
            )
        );

        echo '</div>';
    }

    public function filter_woocommerce_product_custom_fields($post_id)
    {
        $product = wc_get_product($post_id);
        if ($product->is_type('variable-subscription')) {
            $this->handle_saving_variable_subscription($product);
        }
        if ($product->is_type('subscription')) {
            $this->handle_saving_simple_subscription($product);
        }
    }

    private function handle_saving_variable_subscription($product)
    {
        $variations = array_reverse($product->get_children());
        $periods = $this->get_post_vars('variable_subscription_period');
        $intervals = $this->get_post_vars('variable_subscription_period_interval');

        foreach ($variations as $key => $variation) {
            $installments = $this->get_post_vars("vindi_max_credit_installments_$variation");
            if (isset($periods[$key]) && isset($intervals[$key])) {
                $this->save_woocommerce_product_custom_fields(
                    $variation,
                    $installments,
                    $periods[$key],
                    $intervals[$key]
                );
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function get_post_vars($var)
    {
        if (!empty($_POST) && isset($_POST[$var])) {
            return filter_var($_POST[$var]);
        }

        return false;
    }

    private function handle_saving_simple_subscription($product)
    {
        $post_id = $product->get_id();

        $period = $this->get_post_vars('_subscription_period');
        $interval = $this->get_post_vars('_subscription_period_interval');
        $installments = $this->get_post_vars("vindi_max_credit_installments_$post_id");

        if ($period && $interval && $installments) {
            $this->save_woocommerce_product_custom_fields($post_id, $installments, $period, $interval);
        }
    }

    private function save_woocommerce_product_custom_fields($post_id, $installments, $period, $interval)
    {
        if ($period === 'year' && $installments > 12) {
            $installments = 12;
        }
        if ($period === 'month' && $installments > $interval) {
            $installments = $interval;
        }

        update_post_meta($post_id, "vindi_max_credit_installments_$post_id", $installments);
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
