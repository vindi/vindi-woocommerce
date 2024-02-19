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

class ProductController
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
    private $ignoredTypes;

  function __construct(VindiSettings $vindi_settings)
  {
        $this->routes = $vindi_settings->routes;
        $this->logger = $vindi_settings->logger;

        /**
         * Define wich product types to NOT handle in this controller.
         * Basically they are the same as the PlansController, but
         * the check is reversed to ignore this types
         */
        $this->ignoredTypes = array('variable-subscription', 'subscription');

        add_action('wp_insert_post', array($this, 'create'), 10, 3);
        add_action('wp_trash_post', array($this, 'trash'), 10, 1);
        add_action('untrash_post', array($this, 'untrash'), 10, 1);
  }

  /**
   * When the user creates a product in Woocomerce, it is created in the Vindi.
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
        if ($post_meta->check_vindi_item_id($post_id, 'vindi_product_id') > 1) {
            update_post_meta($post_id, 'vindi_product_id', '');
        }

        // Check if it's a new post
        // The $update value is unreliable because of the auto_draft functionality
        if(!$recreated && get_post_status($post_id) != 'publish' || !empty(get_post_meta($post_id, 'vindi_product_id', true))) {
          return $this->update($post_id);
        }

        $product = wc_get_product($post_id);

        // Check if the post is NOT of the subscription type
        if (in_array($product->get_type(), $this->ignoredTypes)) {
          return;
        }

        $data = $product->get_data();

        // Creates the product within the Vindi
        $createdProduct = $this->routes->createProduct(array(
          'name' => VINDI_PREFIX_PRODUCT . $data['name'],
          'code' => 'WC-' . $data['id'],
          'status' => ($data['status'] == 'publish') ? 'active' : 'inactive',
          'invoice' => 'always',
          'pricing_schema' => array(
            'price' => ($data['price']) ? $data['price'] : 0,
            'schema_type' => 'flat',
          )
        ));

              // Saving product id and plan in the WC goal
          if ($createdProduct && isset($createdProduct['id'])) {
            update_post_meta( $post_id, 'vindi_product_id', $createdProduct['id'] );
            set_transient('vindi_product_message', 'created', 60);
          } else {
            set_transient('vindi_product_message', 'error', 60);
          }

        return $createdProduct;
  }

  function update($post_id)
  {
        $product = wc_get_product($post_id);

        // Check if the post is NOT of the subscription type
        if (in_array($product->get_type(), $this->ignoredTypes)) {
          return;
        }

        // Checks whether there is a vindi product ID associated within
        $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);

        if(empty($vindi_product_id)) {

          return $this->create($post_id, '', '', true);
        }

        $data = $product->get_data();

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

        if($updatedProduct) {
          set_transient('vindi_product_message', 'updated', 60);
        } else {
          set_transient('vindi_product_message', 'error', 60);
        }

        return $updatedProduct;
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

        // Check if the post is NOT of the subscription type
        if (in_array($product->get_type(), $this->ignoredTypes)) {
          return;
        }

        $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);

        if(empty($vindi_product_id)) {
          return;
        }

        // Changes the product status within the Vindi
        $inactivatedProduct = $this->routes->updateProduct($vindi_product_id, array(
          'status' => 'inactive',
        ));

        return $inactivatedProduct;
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

        // Check if the post is NOT of the subscription type
        if (in_array($product->get_type(), $this->ignoredTypes)) {
          return;
        }

        $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);

        if(empty($vindi_product_id)) {
          return;
        }

        // Changes the product status within the Vindi
        $activatedProduct = $this->routes->updateProduct($vindi_product_id, array(
          'status' => 'active',
        ));

        return $activatedProduct;
  }
}
