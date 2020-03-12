<?php

global $woocommerce;


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

  function __construct()
  {

    $this->routes = new VindiRoutes();

    $this->types = array('variable-subscription', 'subscription');

    add_action('woocommerce_admin_process_product_object', array($this, 'create'), 10, 3);
  }


  function create($product)
  {

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->types)) {
      return;
    }

    $data = $product->get_data();

    // // Checks whether it is a new product or not
    // $created_at = strtotime($product->get_date_created()->format('Y-m-d H:i:s'));
    // $updated_at = strtotime($product->get_date_modified()->format('Y-m-d H:i:s'));

    // if ($update_at < $created_at) {
    //   return $this->update($post_id, $post);
    // }

    // VindiHelpers::wc_post_meta($data['id'], array(
    //   'vindi_product_id' => 11,
    //   'vindi_plan_id' => 10,
    // ));

    $this->routes->createPlan(array(
      'name' => 'thiago chandon',
      'interval' => 'days',
      'interval_count' => 20,
      'billing_trigger_type' => 'beginning_of_period',
      'billing_trigger_day' => 10,
      'billing_cycles' => 9,
      'code' => '10299',
      'description' => '123123123',
      'installments' => 1,
      // 'invoice_split' => true,
      'status' => 'active',
      // 'plan_items' => array(
      //   array(
      //     'cycles' => 1,
      //     'product_id' => 0
      //   ),
      // ),
    ));

    print_r($createPlan);

    die();
    // $subscription = $this->get_product(33);
  }

  function update($post_id, $post)
  {

    $subscription = $this->get_product(33);
  }
}

$seila = new PlansController();
