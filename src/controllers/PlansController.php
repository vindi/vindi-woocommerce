<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Product;

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

  /**
   * @var VindiLogger
   */
  private $logger;

  /**
   * @var array
   */
  private $allowedTypes;

  function __construct(VindiSettings $vindi_settings)
  {
    $this->routes = $vindi_settings->routes;
    $this->logger = $vindi_settings->logger;
    $this->allowedTypes = array('variable-subscription', 'subscription');

    add_action('wp_insert_post', array($this, 'handle_post_insert'), 20, 3);
    add_action('woocommerce_update_product', array($this, 'update'), 10, 2);
    add_action('wp_trash_post', array($this, 'trash'), 10, 1);
    add_action('untrash_post', array($this, 'untrash'), 10, 1);
  }

  function handle_post_insert($post_id, $post, $update)
  {
    if ($post->post_type !== 'product') {
      return;
    }

    if (str_contains($post->post_status, 'draft')) {
      return;
    }

    $product = wc_get_product($post_id);

    if (!$product || !in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    $vindi_plan_id = $product->get_meta('vindi_plan_id', true);
    if (!empty($vindi_plan_id)) {
      return;
    }

    $this->create($post_id, $product);
  }

  /**
   * When the user creates a subscription in Woocomerce, it is created in the Vindi.
   *
   * @since 1.2.2
   * @version 1.2.0
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  function create($product_id, $product = null)
  {
    if (!$product) {
      $product = wc_get_product($product_id);
    }

    if (!$product) {
      return;
    }

    $post_status = $product->get_status();
    if (str_contains($post_status, 'draft')) {
      return;
    }

    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    $vindi_plan_id = $product->get_meta('vindi_plan_id', true);
    if (!empty($vindi_plan_id)) {
      return;
    }

    $post_meta = new PostMeta();
    if ($post_meta->check_vindi_item_id($product_id, 'vindi_plan_id') > 1) {
      $product->update_meta_data('vindi_plan_id', '');
      $product->save_meta_data();
    }
    if ($post_meta->check_vindi_item_id($product_id, 'vindi_product_id') > 1) {
      $product->update_meta_data('vindi_product_id', '');
      $product->save_meta_data();
    }

    // Checks if the plan is a variation and creates it
    if ($product->get_type() == 'variable-subscription') {
      $variations = $product->get_available_variations();
      $variations_products = $variations_plans = [];

      foreach ($variations as $variation) {
        $variation_product = wc_get_product($variation['variation_id']);

        $data = $variation_product->get_data();

        $interval_type     = $variation_product->get_meta('_subscription_period');
        $interval_count    = $variation_product->get_meta('_subscription_period_interval');
        $plan_interval     = VindiConversions::convert_interval($interval_count, $interval_type);
        $variation_id      = $variation['variation_id'];

        $plan_installments = $variation_product->get_meta("vindi_max_credit_installments_$variation_id");

        if (!$plan_installments || $plan_installments === 0) {
          $plan_installments = 1;
        }

        $trigger_day = VindiConversions::convertTriggerToDay(
          $product->get_meta('_subscription_trial_length'),
          $product->get_meta('_subscription_trial_period')
        );

        // Creates the product within the Vindi
        $vindi_product_id = $product->get_meta('vindi_product_id', true);
        $createdProduct = !empty($vindi_product_id) ?
          $this->routes->findProductById($vindi_product_id) :
          $this->routes->createProduct(
            array(
              'name' => VINDI_PREFIX_PRODUCT . $data['name'],
              'code' => 'WC-' . $data['id'],
              'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
              'invoice' => 'always',
              'pricing_schema' => array(
                'price' => ($data['price']) ? $data['price'] : 0,
                'schema_type' => 'flat',
              )
            )
          );

        // Creates the plan within the Vindi
        $createdPlan = $this->routes->createPlan(array(
          'name' => VINDI_PREFIX_PLAN . $data['name'],
          'interval' => $plan_interval['interval'],
          'interval_count' => $plan_interval['interval_count'],
          'billing_trigger_type' => 'beginning_of_period',
          'billing_trigger_day' => $trigger_day,
          'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
          'code' => 'WC-' . $data['id'],
          'installments' => $plan_installments,
          'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          'plan_items' => array(
            ($product->get_meta('_subscription_length') == 0) ? array(
              'product_id' => $createdProduct['id']
            ) : array(
              'cycles' => $product->get_meta('_subscription_length'),
              'product_id' => $createdProduct['id']
            )
          ),
        ));
        $variations_products[$variation['variation_id']] = $createdProduct;
        $variations_plans[$variation['variation_id']] = $createdPlan;

        if (isset($variation['variation_id']) && $createdProduct['id']) {
          $variation_product = wc_get_product($variation['variation_id']);
          $variation_product->update_meta_data('vindi_product_id', $createdProduct['id']);
          $variation_product->save_meta_data();
        }

        if (isset($variation['variation_id']) && $createdPlan['id']) {
          $variation_product = wc_get_product($variation['variation_id']);
          $variation_product->update_meta_data('vindi_plan_id', $createdPlan['id']);
          $variation_product->save_meta_data();
        }
      }

      $variation_id = array_key_last($variations_products);

      if ($variation_id) {
        $variation_product = wc_get_product($variation_id);
        if ($variation_product) {
          $variation_product->update_meta_data('vindi_product_id', end($variations_products)['id']);
          $variation_product->update_meta_data('vindi_plan_id', end($variations_plans)['id']);
          $variation_product->save_meta_data();
        }
      }

      return array(
        'product' => $variations_products,
        'plan' => $variations_plans,
      );
    }

    $data = $product->get_data();

    if (class_exists('WC_Subscriptions_Product')) {
      $interval_type = WC_Subscriptions_Product::get_period($product);
      $interval_count = WC_Subscriptions_Product::get_interval($product);
      $subscription_length = WC_Subscriptions_Product::get_length($product);
    } else {
      $interval_type = $product->get_meta('_subscription_period');
      $interval_count = $product->get_meta('_subscription_period_interval');
      $subscription_length = $product->get_meta('_subscription_length');
    }
    $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);

    $trigger_day = VindiConversions::convertTriggerToDay(
      $product->get_meta('_subscription_trial_length'),
      $product->get_meta('_subscription_trial_period')
    );


    $plan_installments = $product->get_meta("vindi_max_credit_installments_$product_id");
    if (!$plan_installments || $plan_installments === 0) {
      $plan_installments = 1;
    }

    // Creates the product within the Vindi
    $vindi_product_id = $product ? $product->get_meta('vindi_product_id', true) : '';
    $createdProduct = !empty($vindi_product_id) ?
      $this->routes->findProductById($vindi_product_id) :
      $this->routes->createProduct(
        array(
          'name' => VINDI_PREFIX_PRODUCT . $data['name'],
          'code' => 'WC-' . $data['id'],
          'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          'invoice' => 'always',
          'pricing_schema' => array(
            'price' => ($data['price']) ? $data['price'] : 0,
            'schema_type' => 'flat',
          )
        )
      );

    // Creates the plan within the Vindi
    $createdPlan = $this->routes->createPlan(array(
      'name' => VINDI_PREFIX_PLAN . $data['name'],
      'interval' => $plan_interval['interval'],
      'interval_count' => $plan_interval['interval_count'],
      'billing_trigger_type' => 'beginning_of_period',
      'billing_trigger_day' => $trigger_day,
      'billing_cycles' => ($subscription_length == 0) ? null : $subscription_length,
      'code' => 'WC-' . $data['id'],
      'installments' => $plan_installments,
      'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
      'plan_items' => array(
        ($subscription_length == 0) ? array(
          'product_id' => $createdProduct['id']
        ) : array(
          'cycles' => $subscription_length,
          'product_id' => $createdProduct['id']
        )
      ),
    ));


    if ($createdProduct && isset($createdProduct['id'])) {
      $product->update_meta_data('vindi_product_id', $createdProduct['id']);
      $product->save_meta_data();
    }
    if ($createdPlan && isset($createdPlan['id'])) {
      $product->update_meta_data('vindi_plan_id', $createdPlan['id']);
      $product->save_meta_data();
    }

    if ($createdPlan && $createdProduct) {
      set_transient('vindi_product_message', 'created', 60);
    } else {
      set_transient('vindi_product_message', 'error', 60);
    }

    $response = array(
      'product' => $createdProduct,
      'plan' => $createdPlan,
    );

    return $response;
  }

  function update($product_id, $product = null)
  {
    if (!$product) {
      $product = wc_get_product($product_id);
    }

    if (!$product) {
      return;
    }

    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    if ($product->get_type() == 'subscription') {
      $vindi_plan_id = $product->get_meta('vindi_plan_id', true);
      if (empty($vindi_plan_id)) {
        return $this->create($product_id, $product);
      }
    }

    // Checks if the plan is a variation and creates it
    if ($product->get_type() == 'variable-subscription') {

      $variations = $product->get_available_variations();
      $variations_products = $variations_plans = [];

      foreach ($variations as $variation) {
        $variation_product = wc_get_product($variation['variation_id']);

        // Checks whether there is a vindi plan ID created within
        $vindi_plan_id = $variation_product->get_meta('vindi_plan_id', true);
        $vindi_product_id = $variation_product->get_meta('vindi_product_id', true);

        if (empty($vindi_plan_id)) {
          return $this->create($product_id, $product);
        }

        $data = $variation_product->get_data();
        $interval_type     = $variation_product->get_meta('_subscription_period');
        $interval_count    = $variation_product->get_meta('_subscription_period_interval');
        $plan_interval     = VindiConversions::convert_interval($interval_count, $interval_type);
        $variation_id      = $variation['variation_id'];

        $plan_installments = $variation_product->get_meta("vindi_max_credit_installments_$variation_id");

        if (!$plan_installments || $plan_installments === 0) {
          $plan_installments = 1;
        }

        $trigger_day = VindiConversions::convertTriggerToDay(
          $product->get_meta('_subscription_trial_length'),
          $product->get_meta('_subscription_trial_period')
        );

        // Updates the product within the Vindi
        $updatedProduct = $this->routes->updateProduct(
          $vindi_product_id,
          array(
            'name' => VINDI_PREFIX_PRODUCT . $data['name'],
            'code' => 'WC-' . $data['id'],
            'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
            'invoice' => 'always',
            'pricing_schema' => array(
              'price' => ($data['price']) ? $data['price'] : 0,
              'schema_type' => 'flat',
            )
          )
        );

        // Updates the plan within the Vindi
        $updatedPlan = $this->routes->updatePlan(
          $vindi_plan_id,
          array(
            'name' => VINDI_PREFIX_PLAN . $data['name'],
            'interval' => $plan_interval['interval'],
            'interval_count' => $plan_interval['interval_count'],
            'billing_trigger_type' => 'beginning_of_period',
            'billing_trigger_day' => $trigger_day,
            'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
            'code' => 'WC-' . $data['id'],
            'installments' => $plan_installments,
            'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          )
        );

        $variations_products[$variation['variation_id']] = $updatedProduct;
        $variations_plans[$variation['variation_id']] = $updatedPlan;
      }

      return array(
        'product' => $variations_products,
        'plan' => $variations_plans,
      );
    }

    $data = $product->get_data();

    $interval_type = $product->get_meta('_subscription_period');
    $interval_count = $product->get_meta('_subscription_period_interval');
    $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);

    $trigger_day = VindiConversions::convertTriggerToDay(
      $product->get_meta('_subscription_trial_length'),
      $product->get_meta('_subscription_trial_period')
    );

    $vindi_product_id = $product->get_meta('vindi_product_id', true);

    // Updates the product within the Vindi
    $updatedProduct = $this->routes->updateProduct(
      $vindi_product_id,
      array(
        'name' => VINDI_PREFIX_PRODUCT . $data['name'],
        'code' => 'WC-' . $data['id'],
        'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
        'invoice' => 'always',
        'pricing_schema' => array(
          'price' => ($data['price']) ? $data['price'] : 0,
          'schema_type' => 'flat',
        )
      )
    );

    $vindi_plan_id     = $product->get_meta('vindi_plan_id', true);
    $plan_installments = $product->get_meta("vindi_max_credit_installments_$product_id");
    if (!$plan_installments || $plan_installments === 0) {
      $plan_installments = 1;
    }

    // Updates the plan within the Vindi
    $updatedPlan = $this->routes->updatePlan(
      $vindi_plan_id,
      array(
        'name' => VINDI_PREFIX_PLAN . $data['name'],
        'interval' => $plan_interval['interval'],
        'interval_count' => $plan_interval['interval_count'],
        'billing_trigger_type' => 'beginning_of_period',
        'billing_trigger_day' => $trigger_day,
        'billing_cycles' => ($product->get_meta('_subscription_length') == 0) ? null : $product->get_meta('_subscription_length'),
        'code' => 'WC-' . $data['id'],
        'installments' => $plan_installments,
        'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
      )
    );

    if ($updatedPlan && $updatedProduct) {
      set_transient('vindi_product_message', 'updated', 60);
    } else {
      set_transient('vindi_product_message', 'error', 60);
    }
    $response = array(
      'product' => $updatedProduct,
      'plan' => $updatedPlan,
    );

    return $response;
  }

  /**
   * When the user trashes a product in Woocomerce, it is deactivated in the Vindi.
   *
   * @since 1.0.1
   * @version 1.0.1
   */
  function trash($post_id)
  {
    $product = wc_get_product($post_id);

    if (!$product) {
      return;
    }
    // Check if the post is product
    if ($product->get_type() != 'product') {
      return;
    }

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    $vindi_product_id = $product->get_meta('vindi_product_id', true);
    $vindi_plan_id = $product->get_meta('vindi_plan_id', true);

    if (empty($vindi_product_id) || empty($vindi_plan_id)) {
      return;
    }

    // Changes the product status within the Vindi
    $inactivatedProduct = $this->routes->updateProduct($vindi_product_id, array(
      'status' => 'inactive',
    ));

    // Changes the plan status within the Vindi
    $inactivatedPlan = $this->routes->updatePlan($vindi_plan_id, array(
      'status' => 'inactive',
    ));

    return array(
      'product' => $inactivatedProduct,
      'plan' => $inactivatedPlan,
    );
  }

  /**
   * When the user untrashes a product in Woocomerce, it is activated in the Vindi.
   *
   * @since 1.0.01
   * @version 1.0.0
   */
  function untrash($post_id)
  {
    $product = wc_get_product($post_id);

    if (!$product) {
      return;
    }
    // Check if the post is product
    if ($product->get_type() != 'product') {
      return;
    }

    // Check if the post is of the signature type
    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    $vindi_product_id = $product->get_meta('vindi_product_id', true);
    $vindi_plan_id = $product->get_meta('vindi_plan_id', true);

    if (empty($vindi_product_id) || empty($vindi_plan_id)) {
      return;
    }

    // Changes the product status within the Vindi
    $activatedProduct = $this->routes->updateProduct($vindi_product_id, array(
      'status' => 'active',
    ));

    // Changes the plan status within the Vindi
    $activatedPlan = $this->routes->updatePlan($vindi_plan_id, array(
      'status' => 'active',
    ));

    return array(
      'product' => $activatedProduct,
      'plan' => $activatedPlan,
    );
  }
}
