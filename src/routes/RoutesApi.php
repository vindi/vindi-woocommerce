<?php

namespace VindiPaymentGateways;

class VindiRoutes
{

  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  /**
   * @var void
   */
  public $api;


  function __construct(VindiSettings $vindi_settings)
  {

    $this->vindi_settings = $vindi_settings;
    $this->api = $this->vindi_settings->api;
  }

  /**
   * Enough if there is a product within Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function findProductById($product_id)
  {
    $response = $this->api->request(sprintf(
      'products/%s',
      filter_var($product_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'GET');

    $productExists = isset($response['product']['id']) ? $response['product'] : false;

    return $productExists;
  }


  /**
   * Post method for creating plan in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function createPlan($data)
  {

    $response = $this->api->request('plans', 'POST', $data);

    return $response['plan'];
  }

    /**
     * Update method for updating plan in the Vindi
     *
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public function renewCharge($charge_id)
    {
      $response = $this->api->request(
          sprintf(
              'charges/%s/charge',
              filter_var($charge_id, FILTER_SANITIZE_NUMBER_INT)
          ),
          'POST',
          []
      );
    
      return $response['charge'];
    }

  /**
   * Update method for updating plan in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function updatePlan($plan_id, $data)
  {

    $response = $this->api->request(sprintf(
      'plans/%s',
      filter_var($plan_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'PUT', $data);

    return $response['plan'];
  }

  /**
   * Post method for creating product in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function createProduct($data)
  {

    $response = $this->api->request('products', 'POST', $data);
    return $response['product'];
  }

  /**
   * Update method for updating product in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function updateProduct($product_id, $data)
  {

    $response = $this->api->request(sprintf(
      'products/%s',
      filter_var($product_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'PUT', $data);
    return $response['product'];
  }

  /**
   * Post method for creating customer in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function createCustomer($data)
  {
    $response = $this->api->request('customers', 'POST', $data);
        if (isset($response['customer'])) {
          return $response['customer'];
        }

        return [];
  }

  /**
   * Update method for update profile customer in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function updateCustomer($user_id, $data)
  {

    $response = $this->api->request(sprintf(
      'customers/%s',
      filter_var($user_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'PUT', $data);

    return $response['customer'];
  }

  /**
   * Delete method to disable the customer in the Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function deleteCustomer($user_id)
  {

    $response = $this->api->request(sprintf(
      'customers/%s',
      filter_var($user_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'DELETE');

    return $response['customer'];
  }


  /**
   * Check if exists user in Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function findCustomerById($id)
  {

    $response = $this->api->request(sprintf(
      'customers/%s',
      filter_var($id, FILTER_SANITIZE_NUMBER_INT)
    ), 'GET');

    $userExists = isset($response['customer']['id']) ? $response['customer'] : false;

    return $userExists;
  }

  /**
   * @param $data (plan_id, customer_id, payment_method_code, product_items[{product_id}])
   *
   * @return array
   */
  public function createSubscription($data)
  {
    if (($response = $this->api->request('subscriptions', 'POST', $data)) &&
      isset($response['subscription']['id'])) {

      $subscription         = $response['subscription'];
      $subscription['bill'] = $response['bill'];

      return $subscription;
    }

    return false;
  }

  /**
   * @param int $subscription_id
   * @param bool $cancel_bills
   *
   * @return array|bool|mixed
   */
  public function suspendSubscription($subscription_id, $cancel_bills = false)
  {
    $query = '';

    if(!$cancel_bills)
      $query = '?cancel_bills=false';

    $response = $this->api->request(
      sprintf('subscriptions/%s%s', filter_var($subscription_id, FILTER_SANITIZE_NUMBER_INT), $query), 'DELETE');

    return $response;
  }

  /**
   * @param int   $subscription_id
   *
   * @return array|bool|mixed
   */
  public function activateSubscription($subscription_id)
  {
    if ($response = $this->api->request('subscriptions/' . filter_var($subscription_id, FILTER_SANITIZE_NUMBER_INT) . '/reactivate', 'POST'))
      return $response;

    return false;
  }

  /**
	 * @param int   $subscription_id
	 *
	 * @return array|bool|mixed
	 */
	public function getSubscription($subscription_id)
	{
		if ($response = $this->api->request("subscriptions/". filter_var($subscription_id, FILTER_SANITIZE_NUMBER_INT),'GET')['subscription'])
			return $response;

		return false;
  }

  /**
   * @param string $subscription_id
   *
   * @return bool
   */
  public function isSubscriptionActive($subscription_id)
  {
    $subscription_id = filter_var($subscription_id, FILTER_SANITIZE_NUMBER_INT);
    if (isset($this->recentRequest)
      && $this->recentRequest['id'] == $subscription_id) {
      if ($this->recentRequest['status'] != 'canceled')
        return true;
      return false;
    }

    $response = $this->getSubscription($subscription_id);

    if ($response && array_key_exists('status', $response)) {
      if ($response['status'] != 'canceled') {
        $this->recentRequest = $response;
        return true;
      }
    }
    return false;
  }

  public function verifyCustomerPaymentProfile($payment_profile_id)
  {
    return 'success' === $this->api->request(sprintf(
      'payment_profiles/%s/verify',
      filter_var($payment_profile_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'POST')['transaction']['status'];
  }

  public function createCustomerPaymentProfile($data)
  {
    // Protect credit card number.
    $log = $data;
        if (isset($data['card_number']) && isset($data['card_cvv'])) {
          $log['card_number'] = sprintf('**** *%s', substr($log['card_number'], -3));
          $log['card_cvv'] = '***';
        }

    $response = $this->api->request('payment_profiles', 'POST', $data, $log);

        if (isset($response['payment_profile'])) {
          return $response['payment_profile'];
        }

        return [];
  }

  public function findProductByCode($code)
  {
    $code = sanitize_text_field($code);
    $transient_key = "vindi_product_{$code}";
    $product = get_transient($transient_key);

    if(false !== $product)
      return $product;

    $response = $this->api->request(sprintf('products?query=code:%s', $code), 'GET');

    if (false === empty($response['products'])) {
      $product = end($response['products']);
      set_transient($transient_key, $product, 1 * HOUR_IN_SECONDS);
    }

    return $product;
  }

  public function findOrCreateProduct($name, $code)
  {
    $name = sanitize_text_field($name);
    $code = sanitize_text_field($code);
    $product = $this->findProductByCode($code);

    if (false === $product)
    {
      return $this->createProduct(array(
        'name'           => $name,
        'code'           => $code,
        'status'         => 'active',
        'pricing_schema' => array(
          'price' => 0,
        ),
      ));
    }

    return $product;
  }

  public function createBill($data)
  {
    if ($response = $this->api->request('bills', 'POST', $data)) {
      return $response['bill'];
    }

    return false;
  }

  public function deleteBill($bill_id, $comments = '')
  {
    $query = '';

    if($comments)
      $query = '?comments= ' . $comments;

    if ($response = $this->api->request(
      sprintf('bills/%s%s', filter_var($bill_id, FILTER_SANITIZE_NUMBER_INT), $query), 'DELETE')
    ) {
      return $response;
    }

    return false;
  }

  public function getPaymentMethods()
  {
    if (false === ($payment_methods = get_transient('vindi_payment_methods'))) {

      $payment_methods = array(
        'credit_card' => array(),
        'bank_slip'   => false,
      );

      $response = $this->api->request('payment_methods', 'GET');

      if (false == $response)
        return false;

      foreach ($response['payment_methods'] as $method) {
        if ('active' !== $method['status']) {
          continue;
        }

        if ('PaymentMethod::CreditCard' === $method['type']) {
          $payment_methods['credit_card'] = array_merge(
            $payment_methods['credit_card'],
            $method['payment_companies']
          );
        } else if ('PaymentMethod::BankSlip' === $method['type'] || 'PaymentMethod::OnlineBankSlip' === $method['type']) {
          $payment_methods['bank_slip'] = true;
        }
      }

      set_transient('vindi_payment_methods', $payment_methods, 1 * HOUR_IN_SECONDS);
    }

    $this->api->accept_bank_slip = $payment_methods['bank_slip'];

    return $payment_methods;
  }

  public function isMerchantStatusTrialOrSandbox($is_config = false)
  {
        if ('yes' === isset($this->sandbox)) {
            return true;
        }

      $merchant = $is_config ? $this->getMerchant($is_config) : $this->getMerchant();

        if ('trial' === $merchant['status']) {
          return true;
        }

      return false;
  }

  public function getMerchant($is_config = false)
  {
    if (false === ($merchant = get_transient('vindi_merchant')) || $is_config) {
      $response = $this->api->request('merchant', 'GET');

      if (!$response || !$response['merchant'])
        return false;

      $merchant = $response['merchant'];

      set_transient('vindi_merchant', $merchant, 1 * HOUR_IN_SECONDS);
    }

    return $merchant;
  }

  public function getCharge($charge_id)
  {
    $response = $this->api->request("charges/" . filter_var($charge_id, FILTER_SANITIZE_NUMBER_INT), 'GET');

    if (empty($response['charge']))
      return false;

    return $response['charge'];
  }

  /**
   * @var array|bool|mixed
   */
    public $current_plan;

  public function getPlan($plan_id)
  {
    $response = $this->api->request("plans/" . filter_var($plan_id, FILTER_SANITIZE_NUMBER_INT), 'GET');

    if (empty($response['plan'])) {
      $this->current_plan = false;
      return false;
    }
    $this->current_plan = $response['plan'];
    return $this->current_plan;
  }

  public function getPaymentProfile($user_vindi_id)
  {
    $user_vindi_id = filter_var($user_vindi_id, FILTER_SANITIZE_NUMBER_INT);
    $customer = $this->findCustomerById($user_vindi_id);

    if (empty($customer))
      return false;

    $query    = urlencode("customer_id={$customer['id']} status=active type=PaymentProfile::CreditCard");
    $response = $this->api->request('payment_profiles?query='.$query, 'GET');

    if (end($response['payment_profiles']) !== null)
      return end($response['payment_profiles']);

    return false;
  }

  public function acceptBankSlip()
  {
    if (null === $this->api->accept_bank_slip) {
      $this->getPaymentMethods();
    }

    return $this->api->accept_bank_slip;
  }

  /**
   * Enough if there is a product within Vindi
   *
   * @since 1.0.0
   * @version 1.0.0
   * @return array
   */
  public function findBillById($bill_id)
  {

    $response = $this->api->request(sprintf(
      'bills/%s',
      filter_var($bill_id, FILTER_SANITIZE_NUMBER_INT)
    ), 'GET');

    if (isset($response['bill']))
      return $response['bill'];

    return false;
  }

  public function refundCharge($charge_id, $data)
  {
    $response = $this->api->request(sprintf('charges/%s/refund', filter_var($charge_id, FILTER_SANITIZE_NUMBER_INT)), 'POST', $data);

    if (isset($response['charge'])) {
      return $response['charge'];
    }

    return false;
  }

    public function hasPendingSubscriptionBills($subscription_id)
    {
        $bill_subscription_id = filter_var($subscription_id, FILTER_SANITIZE_NUMBER_INT);
        $query = urlencode("subscription_id={$bill_subscription_id} status=pending");

        $response = $this->api->request('bills?query=' . $query, 'GET');

        if (empty($response['bills'])) {
            return false;
        }

        return true;
    }
}
?>
