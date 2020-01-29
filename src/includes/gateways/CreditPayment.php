<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * vindi Payment Credit Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Vindi_Credit_Gateway
 * @extends WC_Payment_Gateway
 */
class WC_Vindi_Credit_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		global $woocommerce;

		$this->id                   = 'vindi-credit-card';
		$this->icon                 = apply_filters( 'vindi_woocommerce_credit_card_icon', '' );
		$this->method_title         = __( 'Vindi - Credit card', 'vindi-woocommerce' );
		$this->method_description   = __( 'Accept credit card payments using Vindi.', 'vindi-woocommerce' );
		$this->has_fields           = true;
		$this->view_transaction_url = 'https://google.com.br';
		$this->supports             = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'refunds',
			'pre-orders'
		);
	}
}
