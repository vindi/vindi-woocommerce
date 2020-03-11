<?php

global $woocommerce;


class PlansController extends WC_Subscriptions
{

  /**
   * @var VindiApi
   */
  private $api;

  /**
   * @var array
   */
  private $content;

  /**
   * @var array
   */
  private $types;


  /**
   * @param string $key
   * @param string $sandbox
   */

  function __construct($content = '')
  {

    $this->content = $content;
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

    // Checks whether it is a new product or not
    $created_at = strtotime($product->get_date_created()->format('Y-m-d H:i:s'));
    $updated_at = strtotime($product->get_date_modified()->format('Y-m-d H:i:s'));

    if ($update_at < $created_at) {
      return $this->update($post_id, $post);
    }


    $subscription = $this->get_product(33);
  }

  function update($post_id, $post)
  {

    $subscription = $this->get_product(33);

    echo 'Update';
    die();
  }
}

$seila = new PlansController();
