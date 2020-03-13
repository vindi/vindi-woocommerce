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
    // add_action( 'profile_update', $user_id, $old_user_data );
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


    // Checks whether it is a new customer or not
    if (!empty($user['date_modified'])) {

      // --- Not Worked
      return;
    }

    $createUser = $this->routes->creatECustomer(
      array(
        'name' => (!$user['first_name']) ? $user['display_name'] : $user['first_name'] . ' ' . $user['last_name'],
        'email' => ($user['email']) ? $user['email'] : rand() . '@gmail.com',
        'code' => 'WC-' . $user['id'],
        'address' => array(
          'street' => ($user['billing']['address_1']) ? $user['billing']['address_1'] : '',
          'number' => (intval($user['billing']['address_2'])) ? intval($user['billing']['address_2']) : 0,
          'additional_details' => ($user['billing']['address_2']) ?  $user['billing']['address_2'] : '',
          'zipcode' => ($user['billing']['postcode']) ? $user['billing']['postcode'] : '',
          'city' => ($user['billing']['city']) ? $user['billing']['city'] : '',
          'state' => ($user['billing']['state']) ? $user['billing']['state'] : '',
          'country' => ($user['billing']['country']) ? $user['billing']['country'] : ''
        ),
        'phones' => array(
          array(
            'phone_type' => 'mobile',
            'number' => ($user['billing']['phone']) ? $user['billing']['phone'] : '554199999999',
          )
        )
      )
    );
  }

  function update($post_id, $post)
  {

    $subscription = $this->get_product(33);
  }
}
