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
  private array $allowedTypes = ['variable-subscription', 'subscription'];

  function __construct(VindiSettings $vindi_settings)
  {
    $this->routes = $vindi_settings->routes;
    $this->logger = $vindi_settings->logger;

    add_action('woocommerce_new_product', array($this, 'onNewProduct'), 10, 2);
    add_action('woocommerce_update_product', array($this, 'onUpdateProduct'), 10, 2);

    add_action('wp_trash_post', array($this, 'trash'), 10, 1);
    add_action('untrash_post', array($this, 'untrash'), 10, 1);
  }

  /**
   * Reads subscription meta from the most reliable source available.
   *
   * Priority:
   *  1. $_POST (admin context) — because woocommerce_update_product fires
   *     before WCS persists the meta to the database.
   *  2. WC_Subscriptions_Product static helpers — fall back to internal
   *     calculations if available.
   *  3. get_meta() direct read.
   *
   * @return array{interval_type:string,interval_count:int,subscription_length:int,trial_length:int,trial_period:string}
   */
  private function readSubscriptionMeta($product, $product_id)
  {
    if (is_admin() && isset($_POST['_subscription_period'])) {
      return array(
        'interval_type'       => sanitize_text_field(wp_unslash($_POST['_subscription_period'] ?? 'month')),
        'interval_count'      => (int) ($_POST['_subscription_period_interval'] ?? 1),
        'subscription_length' => (int) ($_POST['_subscription_length'] ?? 0),
        'trial_length'        => (int) ($_POST['_subscription_trial_length'] ?? 0),
        'trial_period'        => sanitize_text_field(wp_unslash($_POST['_subscription_trial_period'] ?? 'day')),
      );
    }

    if (class_exists('\WC_Subscriptions_Product')) {
      return array(
        'interval_type'       => (string) \WC_Subscriptions_Product::get_period($product),
        'interval_count'      => (int) \WC_Subscriptions_Product::get_interval($product),
        'subscription_length' => (int) \WC_Subscriptions_Product::get_length($product),
        'trial_length'        => (int) \WC_Subscriptions_Product::get_trial_length($product),
        'trial_period'        => (string) \WC_Subscriptions_Product::get_trial_period($product),
      );
    }

    return array(
      'interval_type'       => (string) ($product->get_meta('_subscription_period') ?: 'month'),
      'interval_count'      => (int) ($product->get_meta('_subscription_period_interval') ?: 1),
      'subscription_length' => (int) $product->get_meta('_subscription_length'),
      'trial_length'        => (int) $product->get_meta('_subscription_trial_length'),
      'trial_period'        => (string) ($product->get_meta('_subscription_trial_period') ?: 'day'),
    );
  }

  function onNewProduct($product_id, $product)
  {

    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    if (str_contains($product->get_status(), 'draft')) {
      return;
    }

    $this->handlePlan($product_id, $product);
  }

  function onUpdateProduct($product_id, $product)
  {
    if (!in_array($product->get_type(), $this->allowedTypes)) {
      return;
    }

    if (str_contains($product->get_status(), 'draft')) {
      return;
    }

    $this->handlePlan($product_id, $product);
  }

  private function handlePlan($product_id, $product)
  {
    // Variable Subscription
    if ($product->get_type() === 'variable-subscription') {

      $variations = $product->get_available_variations();

      if (empty($variations)) {
        return;
      }

      $first_variation = wc_get_product(
        $variations[0]['variation_id']
      );

      if (!$first_variation) {
        return;
      }

      $vindi_plan_id = $first_variation->get_meta(
        'vindi_plan_id',
        true
      );

      if (empty($vindi_plan_id)) {
        $this->create($product_id, $product);
      } else {
        $this->update($product_id, $product);
      }

      return;
    }

    // Simple Subscription
    $vindi_plan_id = $product->get_meta(
      'vindi_plan_id',
      true
    );

    if (empty($vindi_plan_id)) {
      $this->create($product_id, $product);
    } else {
      $this->update($product_id, $product);
    }
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
    $data = $product->get_data();

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

        $meta = $this->readSubscriptionMeta($variation_product, $variation['variation_id']);
        $interval_type       = $meta['interval_type'];
        $interval_count      = $meta['interval_count'];
        $subscription_length = $meta['subscription_length'];
        $trial_length        = $meta['trial_length'];
        $trial_period        = $meta['trial_period'];

        $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);
        if (!is_array($plan_interval)) {
          continue;
        }

        $trigger_day = VindiConversions::convertTriggerToDay($trial_length, $trial_period);
        if ($trigger_day === false) {
          $trigger_day = 0;
        }

        $variation_id      = $variation['variation_id'];

        $plan_installments = $variation_product->get_meta("vindi_max_credit_installments_$variation_id");

        if (!$plan_installments || $plan_installments === 0) {
          $plan_installments = 1;
        }

        // Creates the product within the Vindi
        $vindi_product_id = $variation_product->get_meta('vindi_product_id', true);

        if (empty($vindi_product_id)) {
          // Tenta buscar produto existente por código antes de criar
          $product_code = 'WC-' . $data['id'];
          $existing_product = $this->routes->findProductByCode($product_code);

          if ($existing_product && isset($existing_product['id'])) {
            $createdProduct = $existing_product;
          } else {
            $createdProduct = $this->routes->createProduct(
              array(
                'name' => VINDI_PREFIX_PRODUCT . $data['name'],
                'code' => $product_code,
                'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
                'invoice' => 'always',
                'pricing_schema' => array(
                  'price' => ($data['price']) ? $data['price'] : 0,
                  'schema_type' => 'flat',
                )
              )
            );
          }
        } else {
          $createdProduct = $this->routes->findProductById($vindi_product_id);
        }

        // Busca plano existente por código antes de criar
        $plan_code = 'WC-' . $data['id'];
        $existing_plan = $this->routes->findPlanByCode($plan_code);
        if ($existing_plan && isset($existing_plan['id'])) {
          $createdPlan = $existing_plan;
        } else {
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
        }

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

    $meta = $this->readSubscriptionMeta($product, $product_id);
    $interval_type       = $meta['interval_type'];
    $interval_count      = $meta['interval_count'];
    $subscription_length = $meta['subscription_length'];
    $trial_length        = $meta['trial_length'];
    $trial_period        = $meta['trial_period'];

    $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);
    if (!is_array($plan_interval)) {
      set_transient('vindi_product_message', 'error', 60);
      return;
    }

    $trigger_day = VindiConversions::convertTriggerToDay($trial_length, $trial_period);
    if ($trigger_day === false) {
      $trigger_day = 0;
    }


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

    error_log(
      '_subscription_trial_period=' .
        var_export(
          $product->get_meta('_subscription_trial_period'),
          true
        )
    );
    error_log(
      'POST subscription_period=' .
        var_export($_POST['_subscription_period'] ?? null, true)
    );

    error_log(
      'POST subscription_length=' .
        var_export($_POST['_subscription_length'] ?? null, true)
    );

    error_log(
      'POST subscription_trial_length=' .
        var_export($_POST['_subscription_trial_length'] ?? null, true)
    );

    error_log(
      'POST subscription_trial_period=' .
        var_export($_POST['_subscription_trial_period'] ?? null, true)
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
        return;
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
          return;
        }

        $data = $variation_product->get_data();
        $meta = $this->readSubscriptionMeta($variation_product, $variation['variation_id']);
        $interval_type       = $meta['interval_type'];
        $interval_count      = $meta['interval_count'];
        $subscription_length = $meta['subscription_length'];
        $trial_length        = $meta['trial_length'];
        $trial_period        = $meta['trial_period'];

        $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);
        if (!is_array($plan_interval)) {
          continue;
        }

        $trigger_day = VindiConversions::convertTriggerToDay($trial_length, $trial_period);
        if ($trigger_day === false) {
          $trigger_day = 0;
        }

        $variation_id      = $variation['variation_id'];

        $plan_installments = $variation_product->get_meta("vindi_max_credit_installments_$variation_id");

        if (!$plan_installments || $plan_installments === 0) {
          $plan_installments = 1;
        }

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
            'billing_cycles' => ($subscription_length == 0) ? null : $subscription_length,
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

    $meta = $this->readSubscriptionMeta($product, $product_id);
    $interval_type       = $meta['interval_type'];
    $interval_count      = $meta['interval_count'];
    $subscription_length = $meta['subscription_length'];
    $trial_length        = $meta['trial_length'];
    $trial_period        = $meta['trial_period'];

    $plan_interval = VindiConversions::convert_interval($interval_count, $interval_type);
    if (!is_array($plan_interval)) {
      set_transient('vindi_product_message', 'error', 60);
      return;
    }

    $trigger_day = VindiConversions::convertTriggerToDay($trial_length, $trial_period);
    if ($trigger_day === false) {
      $trigger_day = 0;
    }

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
        'billing_cycles' => ($subscription_length == 0) ? null : $subscription_length,
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
