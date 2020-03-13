<?php

class CostumerController
{

  /**
   * @var VindiRoutes
   */
  private $routes;

  function __construct()
  {

    $this->routes = new VindiRoutes();

    // Fires immediately after a new user is registered.
    add_action('user_register', array($this, 'create'), 10, 4);

    // Fires immediately after an existing user is updated.
    add_action('woocommerce_customer_save_address', array($this, 'update'), 10, 4);
    add_action('woocommerce_save_account_details', array($this, 'update'), 10, 4);
  }

  /**
   * When a new user is created within the WP, it is reflected in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */
  function create($user_id)
  {

    $customer = new WC_Customer($user_id);

    $user = $customer->get_data();

    $createUser = $this->routes->createCustomer(
      array(
        'name' => (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'],
        'email' => ($user['email']) ? $user['email'] : rand() . '@gmail.com',
        'code' => 'WC-' . $user['id'],
        'address' => array(
          'street' => ($customer->get_meta('billing_address_1')) ? $customer->get_meta('billing_address_1') : '',
          'number' => ($customer->get_meta('billing_number')) ? $customer->get_meta('billing_number') : '0',
          'registry_code' => ($customer->get_meta('billing_cpf')) ? $customer->get_meta('billing_cpf') : '00000000000',
          'additional_details' => ($customer->get_meta('billing_address_2')) ?  $customer->get_meta('billing_address_2') : '',
          'zipcode' => ($customer->get_meta('billing_postcode')) ? $customer->get_meta('billing_postcode') : '',
          'neighborhood' => ($customer->get_meta('billing_neighborhood')) ? $customer->get_meta('billing_neighborhood') : '',
          'city' => ($customer->get_meta('billing_city')) ? $customer->get_meta('billing_city') : '',
          'state' => ($customer->get_meta('billing_state')) ? $customer->get_meta('billing_state') : '',
          'country' => ($customer->get_meta('billing_country')) ? $customer->get_meta('billing_country') : ''
        ),
        'phones' => array(
          array(
            'phone_type' => 'mobile',
            'number' => ($customer->get_meta('billing_phone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_phone')) : '554199999999',
          )
        )
      )
    )['customer'];

    // Saving customer in the user meta WP
    update_user_meta($user_id, 'vindi_customer_id', $createUser['id']);
  }


  /**
   * When a user is updated within the WP, it is reflected in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */

  function update($user_id)
  {

    $vindi_customer_id = get_user_meta($user_id, 'vindi_customer_id')[0];

    // Check meta Vindi ID
    if (empty($vindi_customer_id)) {

      return create($user_id);
    }

    // Check user exists in Vindi
    if ($this->routes->findCustomerByid($vindi_customer_id)) {

      return create($user_id);
    }

    $customer = new WC_Customer($user_id);

    $user = $customer->get_data();

    // Update customer profile
    $updateUser = $this->routes->updateCustomer(
      $vindi_customer_id,
      array(
        'name' => (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'],
        'email' => ($user['email']) ? $user['email'] : rand() . '@gmail.com',
        'code' => 'WC-' . $user['id'],
        'address' => array(
          'street' => ($customer->get_meta('billing_address_1')) ? $customer->get_meta('billing_address_1') : '',
          'number' => ($customer->get_meta('billing_number')) ? $customer->get_meta('billing_number') : '0',
          'registry_code' => ($customer->get_meta('billing_cpf')) ? $customer->get_meta('billing_cpf') : '00000000000',
          'additional_details' => ($customer->get_meta('billing_address_2')) ?  $customer->get_meta('billing_address_2') : '',
          'zipcode' => ($customer->get_meta('billing_postcode')) ? $customer->get_meta('billing_postcode') : '',
          'neighborhood' => ($customer->get_meta('billing_neighborhood')) ? $customer->get_meta('billing_neighborhood') : '',
          'city' => ($customer->get_meta('billing_city')) ? $customer->get_meta('billing_city') : '',
          'state' => ($customer->get_meta('billing_state')) ? $customer->get_meta('billing_state') : '',
          'country' => ($customer->get_meta('billing_country')) ? $customer->get_meta('billing_country') : ''
        ),
        'phones' => array(
          array(
            'id' => 4719248,
            'phone_type' => 'mobile',
            'number' => ($customer->get_meta('billing_phone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_phone')) : '554199999999',
          )
        )
      )
    )['customer'];
  }
}
