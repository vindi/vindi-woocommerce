<?php

class CustomerController
{

  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  /**
   * @var VindiRoutes
   */
  private $routes;

  function __construct(VindiSettings $vindi_settings)
  {

    $this->vindi_settings = $vindi_settings;
    $this->routes = $vindi_settings->routes;

    // Fires immediately after a new user is registered.
    add_action('user_register', array($this, 'create'), 10, 4);

    // Fires immediately after an existing user is updated.
    add_action('woocommerce_customer_save_address', array($this, 'update'), 10, 4);
    add_action('woocommerce_save_account_details', array($this, 'update'), 10, 4);
    add_action('delete_user', array($this, 'delete'), 10, 2);
  }

  /**
   * When a new user is created within the WP, it is reflected in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */
  function create($user_id, $order = null)
  {

    $customer = new WC_Customer($user_id);

    $user = $customer->get_data();

    $name = (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'];
    $notes = null;
    $cpf_or_cnpj = null;
    $metadata = null;

    if($order) {
      $metadata = array();
      if ('2' === $order->get_meta('_billing_persontype')) {
        // Pessoa jurídica
        $name = $order->get_billing_company();
        $cpf_or_cnpj = $order->get_meta('_billing_cnpj');
        $notes = sprintf('Nome: %s %s', $order->get_billing_first_name(), $order->get_billing_last_name());
  
        if ($this->vindi_settings->send_nfe_information()) {
          $metadata['inscricao_estadual'] = $order->get_meta('_billing_ie');
        }
      } else {
        // Pessoa física
        $cpf_or_cnpj = $order->get_meta('_billing_cpf');
        $notes = '';
  
        if ($this->vindi_settings->send_nfe_information()) {
          $metadata['carteira_de_identidade'] = $order->get_meta('_billing_rg');
        }
      }
    }

    $createdUser = $this->routes->createCustomer(
      array(
        'name' => $name,
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
            'number' => ($customer->get_meta('billing_cellphone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_cellphone')) : '5599999999999',
          ),
          array(
            'phone_type' => 'landline',
            'number' => ($customer->get_meta('billing_phone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_phone')) : '559999999999',
          )
        ),
        'registry_code' => $cpf_or_cnpj ? $cpf_or_cnpj : '',
        'notes' => $notes ? $notes : '',
        'metadata' => !empty($metadata) ? $metadata : '',
      )
    );

    // Saving customer in the user meta WP
    update_user_meta($user_id, 'vindi_customer_id', $createdUser['id']);
    return $createdUser;
  }


  /**
   * When a user is updated within the WP, it is reflected in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */

  function update($user_id, $order = null)
  {

    $vindi_customer_id = get_user_meta($user_id, 'vindi_customer_id')[0];

    // Check meta Vindi ID
    if (empty($vindi_customer_id)) {

      return $this->create($user_id, $order);
    }

    // Check user exists in Vindi
    $vindiUser = $this->routes->findCustomerById($vindi_customer_id);
    if (!$vindiUser) {

      return $this->create($user_id);
    }

    $customer = new WC_Customer($user_id);

    $user = $customer->get_data();
    $phones = array();
    foreach ($vindiUser['phones'] as $phone) :
      $phones[$phone['phone_type']] = $phone['id'];
    endforeach;

    $name = (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'];
    $notes = null;
    $cpf_or_cnpj = null;
    $metadata = null;

    if($order) {
      $metadata = array();
      if ('2' === $order->get_meta('_billing_persontype')) {
        // Pessoa jurídica
        $name = $order->get_billing_company();
        $cpf_or_cnpj = $order->get_meta('_billing_cnpj');
        $notes = sprintf('Nome: %s %s', $order->get_billing_first_name(), $order->get_billing_last_name());
  
        if ($this->vindi_settings->send_nfe_information()) {
          $metadata['inscricao_estadual'] = $order->get_meta('_billing_ie');
        }
      } else {
        // Pessoa física
        $cpf_or_cnpj = $order->get_meta('_billing_cpf');
        $this->vindi_settings->logger->log(sprintf('Order cpf -> %s', $cpf_or_cnpj));
        $this->vindi_settings->logger->log(sprintf('Customer cpf -> %s', $customer->get_meta('billing_cpf')));
        $notes = '';
  
        if ($this->vindi_settings->send_nfe_information()) {
          $metadata['carteira_de_identidade'] = $order->get_meta('_billing_rg');
        }
        $this->vindi_settings->logger->log(sprintf('Order rg -> %s', $order->get_meta('_billing_rg')));
      }
    }

    // Update customer profile
    $updatedUser = $this->routes->updateCustomer(
      $vindi_customer_id,
      array(
        'name' => $name,
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
            'id' => $phones['mobile'],
            'phone_type' => 'mobile',
            'number' => ($customer->get_meta('billing_cellphone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_cellphone')) : '5599999999999',
          ),
          array(
            'id' => $phones['landline'],
            'phone_type' => 'landline',
            'number' => ($customer->get_meta('billing_phone')) ? preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_phone')) : '559999999999',
          )
        ),
        'registry_code' => $cpf_or_cnpj ? $cpf_or_cnpj : '',
        'notes' => $notes ? $notes : '',
        'metadata' => !empty($metadata) ? $metadata : '',
      )
    );
    return $updatedUser;
  }


  /**
   * When a user is deleted within the WP, it is reflected in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */

  function delete($user_id, $reassign)
  {

    $vindi_customer_id = get_user_meta($user_id, 'vindi_customer_id')[0];

    // Check meta Vindi ID
    if (empty($vindi_customer_id)) {

      return;
    }

    // Check user exists in Vindi
    $vindiUser = $this->routes->findCustomerById($vindi_customer_id);
    if (!$vindiUser) {

      return;
    }

    // Delete customer profile
    $deletedUser = $this->routes->deleteCustomer(
      $vindi_customer_id
    );
  }
}
