<?php

namespace VindiPaymentGateways;

use WC_Customer;

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
    add_action('user_register', array($this, 'create'), 10, 1);

    // Fires immediately after an existing user is updated.
    add_action('woocommerce_customer_save_address', array($this, 'update'), 10, 1);
    add_action('woocommerce_save_account_details', array($this, 'update'), 10, 1);
    add_action('delete_user', array($this, 'delete'), 10, 1);
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

    $phones = [];
    if ($customer->get_meta('billing_cellphone')) {
      $phones[] = array(
        'phone_type' => 'mobile',
        'number' => preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_cellphone'))
      );
    }
    if ($customer->get_billing_phone()) {
      $phones[] = array(
        'phone_type' => 'landline',
        'number' => preg_replace('/\D+/', '', '55' . $customer->get_billing_phone())
      );
    }


    if ($order && method_exists($order, 'needs_payment')) {
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
        'email' => ($user['email']) ? $user['email'] : '',
        'code' => 'WC-USER-' . $user['id'],
        'address' => array(
          'street' => ($customer->get_billing_address_1()) ? $customer->get_billing_address_1() : '',
          'number' => ($customer->get_meta('billing_number')) ? $customer->get_meta('billing_number') : '',
          'additional_details' => ($customer->get_billing_address_2()) ?  $customer->get_billing_address_2() : '',
          'zipcode' => ($customer->get_billing_postcode()) ? $customer->get_billing_postcode() : '',
          'neighborhood' => ($customer->get_meta('billing_neighborhood')) ? $customer->get_meta('billing_neighborhood') : '',
          'city' => ($customer->get_billing_city()) ? $customer->get_billing_city() : '',
          'state' => ($customer->get_billing_state()) ? $customer->get_billing_state() : '',
          'country' => ($customer->get_billing_country()) ? $customer->get_billing_country() : ''
        ),
        'phones' => $phones,
        'registry_code' => $cpf_or_cnpj ? $cpf_or_cnpj : '',
        'notes' => $notes ? $notes : '',
        'metadata' => !empty($metadata) ? $metadata : '',
      )
    );

          if (isset($createdUser['id']) && $createdUser['id']) {
            update_user_meta($user_id, 'vindi_customer_id', $createdUser['id']);
          }

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
    $vindi_customer_id = get_user_meta($user_id, 'vindi_customer_id', true);
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
    $phones = $vindi_phones = [];
    foreach ($vindiUser['phones'] as $phone) {
      $vindi_phones[$phone['phone_type']] = $phone['id'];
    }
    if ($customer->get_meta('billing_cellphone')) {
      $mobile = array(
        'phone_type' => 'mobile',
        'number' => preg_replace('/\D+/', '', '55' . $customer->get_meta('billing_cellphone'))
      );
                if (isset($vindi_phones['mobile'])) {
                    $mobile['id'] = $vindi_phones['mobile'];
                }
      $phones[] = $mobile;
    }
    if ($customer->get_billing_phone()) {
      $landline = array(
        'phone_type' => 'landline',
        'number' => preg_replace('/\D+/', '', '55' . $customer->get_billing_phone())
      );
                if (isset($vindi_phones['landline'])) {
                    $landline['id'] = $vindi_phones['landline'];
                }
      $phones[] = $landline;
    }

    $name = (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'];
    $notes = null;
    $cpf_or_cnpj = null;
    $metadata = null;

    if ($order && method_exists($order, 'needs_payment')) {
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
        'email' => ($user['email']) ? $user['email'] : '',
        'code' => 'WC-USER-' . $user['id'],
        'address' => array(
          'street' => ($customer->get_billing_address_1()) ? $customer->get_billing_address_1() : '',
          'number' => ($customer->get_meta('billing_number')) ? $customer->get_meta('billing_number') : '',
          'additional_details' => ($customer->get_billing_address_2()) ?  $customer->get_billing_address_2() : '',
          'zipcode' => ($customer->get_billing_postcode()) ? $customer->get_billing_postcode() : '',
          'neighborhood' => ($customer->get_meta('billing_neighborhood')) ? $customer->get_meta('billing_neighborhood') : '',
          'city' => ($customer->get_billing_city()) ? $customer->get_billing_city() : '',
          'state' => ($customer->get_billing_state()) ? $customer->get_billing_state() : '',
          'country' => ($customer->get_billing_country()) ? $customer->get_billing_country() : ''
        ),
        'phones' => $phones,
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

  function delete($user_id)
  {

    $vindi_customer_id = get_user_meta($user_id, 'vindi_customer_id', true);

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

    return $deletedUser;
  }
}
