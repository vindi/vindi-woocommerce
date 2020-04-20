
<?php

class VindiRoutes
{

  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  /**
   * @var void
   */
  protected $api;

  function __construct(VindiSettings $vindi_settings)
  {

    $this->settings = $vindi_settings;
    $this->api = $this->settings->api;
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
      'product/%s',
      $product_id
    ), 'GET');


    return $response;
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
  public function updatePlan($plan_id, $data)
  {

    $response = $this->api->request(sprintf(
      'plans/%s',
      $plan_id
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
      $product_id
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
    return $response['customer'];
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
      $user_id
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
      $user_id
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
  public function findCustomerByid($id)
  {

    $response = $this->api->request(sprintf(
      'customers/%s',
      $id
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
      sprintf('subscriptions/%s%s', $subscription_id, $query), 'DELETE');

    return $response;
  }

  public function verifyCustomerPaymentProfile($payment_profile_id)
  {
    return 'success' === $this->api->request(sprintf(
      'payment_profiles/%s/verify',
      $payment_profile_id
    ), 'POST')['transaction']['status'];
  }

  public function createCustomerPaymentProfile($data)
  {
    // Protect credit card number.
    $log = $data;
    $log['card_number'] = sprintf('**** *%s', substr($log['card_number'], -3));
    $log['card_cvv'] = '***';

    $response = $this->api->request('payment_profiles', 'POST', $data, $log);

    return $response['payment_profile'];
  }

  public function findProductByCode($code)
  {
    $transient_key = 'vindi_product_' . $code;
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

  public function deleteBill($bill_id)
  {
    if ($response = $this->api->request('bills/' . $bill_id, 'DELETE'))
      return $response;

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
        } else if ('PaymentMethod::BankSlip' === $method['type']) {
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
    if ('yes' === $this->sandbox)
      return true;

    $merchant = $is_config ? $this->getMerchant($is_config) : $this->getMerchant();
    
    if ('trial' === $merchant['status'])
      return true;
    
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

  public function getPlan($plan_id)
  {
    $response = $this->api->request('plans/' . $plan_id, 'GET');

    if (empty($response['plan'])) {
      $this->current_plan = false;
      return false;
    }
    $this->current_plan = $response['plan'];
    return $this->current_plan;
  }

  public function getPaymentProfile($user_vindi_id)
  {
    $customer = $this->findCustomerByid($user_vindi_id);

    if (empty($customer))
      return false;

    $query    = urlencode("customer_id={$customer['id']} status=active type=PaymentProfile::CreditCard");
    $response = $this->api->request('payment_profiles?query='.$query, 'GET');

    if (isset($response['payment_profiles'][0]))
      return $response['payment_profiles'][0];

    return false;
  }

  public function acceptBankSlip()
  {
    if (null === $this->api->accept_bank_slip) {
      $this->get_payment_methods();
    }

    return $this->api->accept_bank_slip;
  }
}
?>
