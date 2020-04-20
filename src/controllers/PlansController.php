<?php
/**
 * Creation and edition of products with reflection within Vindi
 *
 * Warning, by default, this class does not return any status.
 *
 * @since 1.0.0
 *
 */

class PlansController
{

  /**
   * @var array
   */
  private $types;

  /**
   * @var VindiRoutes
   */
  private $routes;

  function __construct(VindiSettings $vindi_settings)
  {

    $this->routes = new VindiRoutes($vindi_settings);
    $this->logger = $vindi_settings->logger;

    $this->types = array('variable-subscription', 'subscription');

    add_action('wp_insert_post', array($this, 'create'), 10, 3);
    add_action('wp_trash_post', array($this, 'trash'), 10, 1);
    add_action('untrash_post', array($this, 'untrash'), 10, 1);
  }

  /**
   * When the user creates a product in Woocomerce, it is created in the Vindi.
   *
   * @since 1.2.2
   * @version 1.2.0
   */
  function create($post_id, $post, $update, $recreated = false)
  {
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }
    // Check if it's a new post
    // The $update value is unreliable because of the auto_draft functionality
    if(!$recreated && get_post_status($post_id) != 'publish' || !empty(get_post_meta($post_id, 'vindi_product_created', true))) {

      return $this->update($post_id);
    }

    $product = wc_get_product($post_id);

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    // Checks if the plan is a variation and creates it
    if($product->get_type() == 'variable-subscription') {

      $variations = $product->get_available_variations();

      foreach ($variations as $variation) {
        $data = wc_get_product($variation['variation_id']);

        $data = $data->get_data();

        $trigger_day = VindiConversions::convertTriggerToDay(
                        $product->get_meta('_subscription_trial_length'),
                        $product->get_meta('_subscription_trial_period')
                      );

        // Creates the product within the Vindi
        $createProduct = $this->routes->createProduct(array(
          'name' => PREFIX_PRODUCT . $data['name'],
          'code' => 'WC-' . $data['id'],
          'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          'description' => $data['description'],
          'invoice' => 'always',
          'pricing_schema' => array(
            'price' => ($data['price']) ? $data['price'] : 0,
            'schema_type' => 'flat',
          )
        ))['product'];

        // Creates the plan within the Vindi
        $createPlan = $this->routes->createPlan(array(
          'name' => PREFIX_PLAN . $data['name'],
          'interval' => $product->get_meta('_subscription_period') . 's',
          'interval_count' => intval($product->get_meta('_subscription_period_interval')),
          'billing_trigger_type' => 'beginning_of_period',
          'billing_trigger_day' => $trigger_day,
          'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
          'code' => 'WC-' . $data['id'],
          'description' => $data['description'],
          'installments' => 1,
          'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          'plan_items' => array(
            array(
              'cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
              'product_id' => $createProduct['id']
            ),
          ),
        ))['plan'];

        // Saving product id and plan in the WC goal
        update_post_meta( $post_id, 'vindi_product_id', $createProduct['id'] );

        update_post_meta( $post_id, 'vindi_plan_id', $createPlan['id'] );

        update_post_meta( $post_id, 'vindi_product_created', true );

      }

      return;
    }

    $data = $product->get_data();


    $trigger_day = VindiConversions::convertTriggerToDay(
      $product->get_meta('_subscription_trial_length'),
      $product->get_meta('_subscription_trial_period')
    );

    // Creates the product within the Vindi
    $createProduct = $this->routes->createProduct(array(
      'name' => PREFIX_PRODUCT . $data['name'],
      'code' => 'WC-' . $data['id'],
      'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
      'description' => $data['description'],
      'invoice' => 'always',
      'pricing_schema' => array(
        'price' => ($data['price']) ? $data['price'] : 0,
        'schema_type' => 'flat',
      )
    ));

    // Creates the plan within the Vindi
    $createPlan = $this->routes->createPlan(array(
      'name' => PREFIX_PLAN . $data['name'],
      'interval' => $product->get_meta('_subscription_period') . 's',
      'interval_count' => intval($product->get_meta('_subscription_period_interval')),
      'billing_trigger_type' => 'beginning_of_period',
      'billing_trigger_day' => $trigger_day,
      'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
      'code' => 'WC-' . $data['id'],
      'description' => $data['description'],
      'installments' => 1,
      'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
      'plan_items' => array(
        array(
          'cycles' => $product->get_meta('_subscription_length'),
          'product_id' => $createProduct['id']
        ),
      ),
    ));

    // Saving product id and plan in the WC goal
    update_post_meta( $post_id, 'vindi_product_id', $createProduct['id'] );

    update_post_meta( $post_id, 'vindi_plan_id', $createPlan['id'] );

    update_post_meta( $post_id, 'vindi_product_created', true );
  }

  function update($post_id)
  {

    $product = wc_get_product($post_id);

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    // Checks whether there is a vindi plan ID created within
    if($product->get_type() == 'subscription') {

      $vindi_plan_id = get_post_meta($post_id, 'vindi_plan_id');

      if(empty($vindi_plan_id)) {

        return $this->create($post_id, '', '', true);
      }

    }

    // Checks if the plan is a variation and creates it
    if($product->get_type() == 'variable-subscription') {

      $variations = $product->get_available_variations();

      foreach ($variations as $variation) {
        $data = wc_get_product($variation['variation_id']);

        // Checks whether there is a vindi plan ID created within
        $vindi_plan_id = get_post_meta($variation['variation_id'], 'vindi_plan_id');
        $vindi_product_id = get_post_meta($variation['variation_id'], 'vindi_product_id');

        if(empty($vindi_plan_id)) {

          return $this->create($post_id, '', '', true);
          break;
        }

        $data = $data->get_data();

        $trigger_day = VindiConversions::convertTriggerToDay(
                        $product->get_meta('_subscription_trial_length'),
                        $product->get_meta('_subscription_trial_period')
                      );

        // Updates the product within the Vindi
        $updateProduct = $this->routes->updateProduct(
          $vindi_product_id[0],
            array(
            'name' => PREFIX_PRODUCT . $data['name'],
            'code' => 'WC-' . $data['id'],
            'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
            'description' => $data['description'],
            'invoice' => 'always',
            'pricing_schema' => array(
              'price' => ($data['price']) ? $data['price'] : 0,
              'schema_type' => 'flat',
            )
          )
        )['product'];

        // Updates the plan within the Vindi
        $updatePlan = $this->routes->updatePlan(
          $vindi_plan_id[0],
            array(
            'name' => PREFIX_PLAN . $data['name'],
            'interval' => $product->get_meta('_subscription_period') . 's',
            'interval_count' => intval($product->get_meta('_subscription_period_interval')),
            'billing_trigger_type' => 'beginning_of_period',
            'billing_trigger_day' => $trigger_day,
            'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
            'code' => 'WC-' . $data['id'],
            'description' => $data['description'],
            'installments' => 1,
            'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          )
        )['plan'];

      }

      return;
    }

    $data = $product->get_data();

    $trigger_day = VindiConversions::convertTriggerToDay(
      $product->get_meta('_subscription_trial_length'),
      $product->get_meta('_subscription_trial_period')
    );

    $vindi_product_id = get_post_meta($post_id, 'vindi_product_id');

    // Updates the product within the Vindi
    $updateProduct = $this->routes->updateProduct(
      $vindi_product_id[0],
      array(
        'name' => PREFIX_PRODUCT . $data['name'],
        'code' => 'WC-' . $data['id'],
        'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
        'description' => $data['description'],
        'invoice' => 'always',
        'pricing_schema' => array(
          'price' => ($data['price']) ? $data['price'] : 0,
          'schema_type' => 'flat',
        )
      )
    )['product'];


    $vindi_plan_id = get_post_meta($post_id, 'vindi_plan_id');

    // Updates the plan within the Vindi
    $updatePlan = $this->routes->updatePlan(
      $vindi_plan_id[0],
      array(
        'name' => PREFIX_PLAN . $data['name'],
        'interval' => $product->get_meta('_subscription_period') . 's',
        'interval_count' => intval($product->get_meta('_subscription_period_interval')),
        'billing_trigger_type' => 'beginning_of_period',
        'billing_trigger_day' => $trigger_day,
        'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
        'code' => 'WC-' . $data['id'],
        'description' => $data['description'],
        'installments' => 1,
        'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
        )
    )['plan'];

  }

  /**
   * When the user trashes a product in Woocomerce, it is deactivated in the Vindi.
   *
   * @since 1.0.1
   * @version 1.0.1
   */
  function trash($post_id)
  {
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

    $product = wc_get_product($post_id);
    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    $vindi_product_id = $product->get_meta('vindi_product_id');
    $vindi_plan_id = $product->get_meta('vindi_plan_id');

    if(empty($vindi_product_id) || empty($vindi_plan_id)) {
      return;
    }

    // Changes the product status within the Vindi
    $inactiveProduct = $this->routes->updateProduct($vindi_product_id, array(
      'status' => 'inactive',
    ));

    // Changes the plan status within the Vindi
    $inactivePlan = $this->routes->updatePlan($vindi_plan_id, array(
      'status' => 'inactive',
    ));
  }

  /**
   * When the user untrashes a product in Woocomerce, it is activated in the Vindi.
   *
   * @since 1.0.01
   * @version 1.0.0
   */
  function untrash($post_id)
  {
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

    $product = wc_get_product($post_id);
    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    $vindi_product_id = $product->get_meta('vindi_product_id');
    $vindi_plan_id = $product->get_meta('vindi_plan_id');

    if(empty($vindi_product_id) || empty($vindi_plan_id)) {
      return;
    }

    // Changes the product status within the Vindi
    $activeProduct = $this->routes->updateProduct($vindi_product_id, array(
      'status' => 'active',
    ));

    // Changes the plan status within the Vindi
    $activePlan = $this->routes->updatePlan($vindi_plan_id, array(
      'status' => 'active',
    ));
  }
}
