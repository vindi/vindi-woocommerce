<?php

namespace VindiPaymentGateways;

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

    add_action('wp_insert_post', array($this, 'create'), 10, 3);
    add_action('wp_trash_post', array($this, 'trash'), 10, 1);
    add_action('untrash_post', array($this, 'untrash'), 10, 1);
  }

  /**
   * When the user creates a subscription in Woocomerce, it is created in the Vindi.
   *
   * @since 1.2.2
   * @version 1.2.0
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  function create($post_id, $post, $update, $recreated = false)
  {

    // Check if the post is a draft
    if (strpos(get_post_status($post_id), 'draft') !== false) {
      return;
    }
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

        $post_meta = new PostMeta();
        if ($post_meta->check_vindi_item_id($post_id, 'vindi_plan_id') > 1) {
            update_post_meta($post_id, 'vindi_plan_id', '');
        }

        if ($post_meta->check_vindi_item_id($post_id, 'vindi_product_id') > 1) {
            update_post_meta($post_id, 'vindi_product_id', '');
        }

    // Check if it's a new post
    // The $update value is unreliable because of the auto_draft functionality
        $post_status = get_post_status($post_id);
        $vindi_plan_id = get_post_meta($post_id, 'vindi_plan_id', true);

        if (!$recreated && $post_status != 'publish' || !empty($vindi_plan_id)) {
            return $this->update($post_id);
        }

    $product = wc_get_product($post_id);

    // Check if the post is of the subscription type
        if (!in_array($product->get_type(), $this->allowedTypes)) {
          return;
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
        $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);
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

        // Saving product id and plan in the WC goal
                      if (isset($variation['variation_id']) && $createdProduct['id']) {
                            update_post_meta($variation['variation_id'], 'vindi_product_id', $createdProduct['id']);
                      }

                        if (isset($variation['variation_id']) && $createdPlan['id']) {
                            update_post_meta($variation['variation_id'], 'vindi_plan_id', $createdPlan['id']);
                        }
      }

            $product_id = end($variations_products)['id'];

            if ($product_id) {
                update_post_meta($post_id, 'vindi_product_id', end($variations_products)['id']);
                update_post_meta($post_id, 'vindi_plan_id', end($variations_products)['id']);
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

          $plan_installments = $product->get_meta("vindi_max_credit_installments_$post_id");
          if (!$plan_installments || $plan_installments === 0) {
              $plan_installments = 1;
          }

    // Creates the product within the Vindi
    $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);
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


    // Saving product id and plan in the WC goal
          if ($createdProduct && isset($createdProduct['id'])) {
            update_post_meta($post_id, 'vindi_product_id', $createdProduct['id']);
          }
            if ($createdPlan && isset($createdPlan['id'])) {
              update_post_meta($post_id, 'vindi_plan_id', $createdPlan['id']);
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

  function update($post_id)
  {
    $product = wc_get_product($post_id);

    // Check if the post is of the signature type
        if (!in_array($product->get_type(), $this->allowedTypes)) {
          return;
        }

    // Checks whether there is a vindi plan ID created within
    if ($product->get_type() == 'subscription') {

      $vindi_plan_id = get_post_meta($post_id, 'vindi_plan_id', true);

      if (empty($vindi_plan_id)) {

        return $this->create($post_id, '', '', true);
      }
    }

    // Checks if the plan is a variation and creates it
    if ($product->get_type() == 'variable-subscription') {

      $variations = $product->get_available_variations();
      $variations_products = $variations_plans = [];

      foreach ($variations as $variation) {
        $variation_product = wc_get_product($variation['variation_id']);

        // Checks whether there is a vindi plan ID created within
        $vindi_plan_id = get_post_meta($variation['variation_id'], 'vindi_plan_id', true);
        $vindi_product_id = get_post_meta($variation['variation_id'], 'vindi_product_id', true);

        if (empty($vindi_plan_id)) {

          return $this->create($post_id, '', '', true);
          break;
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

    $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);

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

    $vindi_plan_id     = get_post_meta($post_id, 'vindi_plan_id', true);
          $plan_installments = $product->get_meta("vindi_max_credit_installments_$post_id");
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
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

    $product = wc_get_product($post_id);
    // Check if the post is of the signature type
        if (!in_array($product->get_type(), $this->allowedTypes)) {
            return;
        }

    $vindi_product_id = get_post_meta($product->id, 'vindi_product_id', true);
    $vindi_plan_id = get_post_meta($product->id, 'vindi_plan_id', true);

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
    // Check if the post is product
    if (get_post_type($post_id) != 'product') {
      return;
    }

    $product = wc_get_product($post_id);
    // Check if the post is of the signature type
        if (!in_array($product->get_type(), $this->allowedTypes)) {
            return;
        }

    $vindi_product_id = get_post_meta($product->id, 'vindi_product_id', true);
    $vindi_plan_id = get_post_meta($product->id, 'vindi_plan_id', true);

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
