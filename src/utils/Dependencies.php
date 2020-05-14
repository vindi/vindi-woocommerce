<?php

if (!function_exists('get_plugins')) {
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class VindiDependencies
{
  /**
   * @var array
   */
  private static $active_plugins;

  /**
   * Init VindiDependencies.
   */
  public static function init()
  {
    self::$active_plugins = (array) get_option('active_plugins', array());

    if (is_multisite()) {
      self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
  }

  /**
   * Check required plugins
   *
   * @return  boolean
   */
  public static function check()
  {
    if (!self::$active_plugins) {
      self::init();
    }

    $required_plugins = [
      [
        'path' => 'woocommerce/woocommerce.php',
        'plugin' => [
          'name' => 'WooCommerce',
          'url' => 'https://wordpress.org/extend/plugins/woocommerce/',
          'version' => [
            'validation' => '>=',
            'number' => '3.0'
          ]
        ]
      ],
      [
        'path' => 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php',
        'plugin' => [
          'name' => 'WooCommerce Extra Checkout Fields for Brazil',
          'url' => 'https://wordpress.org/extend/plugins/woocommerce-extra-checkout-fields-for-brazil/',
          'version' => [
            'validation' => '>=',
            'number' => '3.5'
          ]
        ]
      ]
    ];

    self::is_wc_subscriptions_active();

    foreach ($required_plugins as $plugin) {
      if (self::is_plugin_active($plugin) == false) {

        self::missing_notice(
          $plugin['plugin']['name'],
          $plugin['plugin']['version']['number'],
          $plugin['plugin']['url']
        );

        return false;
      }

      if (self::verify_plugin_version($plugin) == false) {
        return false;
      }
    }

    return true;
  }

  /**
   * Generate notice content
   *
   * @param string $name Plugin name
   * @param string $version Plugin version 
   * @param string $link Plugin url
   *
   * @return  string
   */
  public static function missing_notice($name, $version, $link)
  {
    echo '<div class="error"><p>' . sprintf(__('O Plugin Vindi WooCommerce depende da versão %s do %s para funcionar!', VINDI), $version, "<a href=\"{$link}\">" . __($name, VINDI) . '</a>') . '</p></div>';
  }

  /**
   * Check if the plugin is active
   *
   * @param array $plugin
   *
   * @return boolean
   */
  public static function is_plugin_active($plugin)
  {
    // class_exists('WC_Payment_Gateway') && class_exists('Extra_Checkout_Fields_For_Brazil')
    if(in_array($plugin['path'], self::$active_plugins) && is_plugin_active($plugin['path'])) {
      if(class_exists('WC_Payment_Gateway') && class_exists('Extra_Checkout_Fields_For_Brazil')) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if the current version of the plugin is at least the minimum required version
   *
   * @param array $plugin
   *
   * @return boolean
   */
  public static function verify_plugin_version($plugin)
  {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . "/" . $plugin['path']);
    $version_match = $plugin['plugin']['version'];
    $version_compare = version_compare(
      $plugin_data['Version'],
      $version_match['number'],
      $version_match['validation']
    );
    
    if ($version_compare == false) {
      add_action(
        'admin_notices',
        self::missing_notice(
          $plugin['plugin']['name'],
          $version_match['number'],
          $plugin['plugin']['url']
        )
      );

      return false;
    }

    return true;
  }

  /**
   * Check if WC Subscriptions is active
   *
   * @return boolean
   */
  public static function is_wc_subscriptions_active()
  {
    $wc_subscriptions = [
      'path' => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
      'plugin' => [
        'name' => 'WooCommerce Subscriptions',
        'url' => 'http://www.woothemes.com/products/woocommerce-subscriptions/',
        'version' => [
          'validation' => '>=',
          'number' => '2.2'
        ]
      ],
    ];

    return self::is_plugin_active($wc_subscriptions) || class_exists('WC_Subscriptions');
  }

  /**
   * Check if WC Memberships is active
   *
   * @return boolean
   */
  public static function is_wc_memberships_active()
  {
    $wc_memberships = [
      'path' => 'woocommerce-memberships/woocommerce-memberships.php',
      'plugin' => [
        'name' => 'WooCommerce Memberships',
        'url' => 'http://www.woothemes.com/products/woocommerce-memberships/'
      ]
    ];
    if(self::is_plugin_active($wc_memberships)) {
      return true;
    }
    return false;
  }
}