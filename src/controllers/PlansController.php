<?php

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

    $this->types = array('variable-subscription', 'subscription');

    add_action('updated_post_meta', array($this, 'create'), 10, 4);
  }

  /**
   * When the user creates a product in Woocomerce, it is created in the Vindi.
   *
   * @since 1.0.0
   * @version 1.0.0
   */
  function create($meta_id, $post_id, $meta_key)
  {

    if ($meta_key != '_edit_lock') { // editing the post
      return;
    }

    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

    $product = wc_get_product($post_id);

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    $data = $product->get_data();

    // Checks whether it is a new product or not
    // --- Not Worked

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
      'billing_trigger_day' => $product->get_meta('_subscription_trial_length'),
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
    ))['plan'];

    // Saving product id and plan in the WC goal
    VindiHelpers::wc_post_meta($data['id'], array(
      'vindi_product_id' => $createProduct['id'],
      'vindi_plan_id' => $createPlan['id'],
    ));
  }

  function update($post_id, $post)
  {

    $subscription = $this->get_product(33);
  }
}
