<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Coupon_Data Class updated with custom fields.
 */
class ProductsMetaBox 
{
    public function __construct()
    {
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'woocommerce_product_custom_fields'] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_woocommerce_product_custom_fields' ] );
    }
    
    public function woocommerce_product_custom_fields () {
        global $woocommerce, $post;

        if ( isset( $post->ID ) ) {
            $product = wc_get_product( $post->ID );
            if ( $product->is_type( 'subscription' ) || $post->post_status === 'auto-draft' ) {

                if( $this->check_credit_payment_active( $woocommerce ) ) {

                    echo '<div class="product_custom_field">';
    
                    woocommerce_wp_text_input(
                        array(
                            'id' => 'vindi_max_credit_installments',
                            'value'   => get_post_meta( get_the_ID(), 'vindi_max_credit_installments', true ),
                            'label' => __( 'Máximo de parcelas com cartão de crédito', 'woocommerce' ),
                            'type' => 'number',
                            'description' => sprintf( 'Esse campo controla a quantidade máxima de parcelas para compras com cartão de crédito. <strong> %s </strong>',
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
            }
        }
    }

    public function save_woocommerce_product_custom_fields( $post_id )
    {
        $subscription_period = isset( $_POST['_subscription_period'] ) ? $_POST['_subscription_period'] : false; 
        $installments = isset( $_POST['vindi_max_credit_installments'] ) ? intval( $_POST['vindi_max_credit_installments'] ) : false;
        $product_type = isset( $_POST['product-type'] ) ? sanitize_text_field( $_POST['product-type'] ) : false;

        if ( strpos( $product_type, 'subscription' ) === false ) return;

        if ( $subscription_period ) {
            if ( $subscription_period === 'year' ) {
                if ( $installments > 12 ) $installments = 12;
            } else {
                $installments = 0;
            }
        }

        update_post_meta( $post_id, 'vindi_max_credit_installments', $installments );
    }

    private function check_credit_payment_active( $wc )
    {
        if ( $wc ) {
            $gateways = $wc->payment_gateways->get_available_payment_gateways();

            foreach ( $gateways as $key => $gateway ) {
                if ( $key === 'vindi-credit-card' ) return true;
            }
        }
    }
}
