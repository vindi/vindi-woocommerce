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
   * Check required critical dependencies
   *
   * @return  boolean
   */
  public static function check_critical_dependencies()
  {
    $critical_dependencies = [
      [
        'name' => 'PHP',
        'version' => [
          'validation' => '>=',
          'number' => VINDI_MININUM_PHP_VERSION
        ]
      ],
      [
        'name' => 'WordPress',
        'version' => [
          'validation' => '>=',
          'number' => VINDI_MININUM_WP_VERSION
        ]
      ]
    ];

    $errors = [];

    foreach ($critical_dependencies as $dependency) {
      $version = $dependency['version'];
      if (!version_compare(PHP_VERSION, $version['number'], $version['validation'])) {
        $name = $dependency['name'];
        $number = $version['number'];
        $notice = function () use ($name, $number) {
          self::critical_dependency_missing_notice($name, $number);
        };
        add_action(
          'admin_notices',
          $notice
        );
        array_push($errors, $plugin);
      }
    }
    if(!empty($errors)) {
      return false;
    }

    return true;
  }

  /**
   * Check required plugins
   *
   * @return  boolean
   */
  public static function check()
  {
    if(!self::check_critical_dependencies()) {
      return false;
    }

    if (!self::$active_plugins) {
      self::init();
    }
    if (current_user_can('install_plugins')) {
      $woocommerce_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
      $ecfb_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil'), 'install-plugin_woocommerce-extra-checkout-fields-for-brazil');
    } else {
      $woocommerce_url = 'https://wordpress.org/extend/plugins/woocommerce/';
      $ecfb_url = 'https://wordpress.org/extend/plugins/woocommerce-extra-checkout-fields-for-brazil/';
    }

    $required_plugins = [
      [
        'path' => 'woocommerce/woocommerce.php',
        'plugin' => [
          'name' => 'WooCommerce',
          'url' =>  $woocommerce_url,
          'version' => [
            'validation' => '>=',
            'number' => '3.0'
          ]
        ]
      ],
      [
        'path' => 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php',
        'plugin' => [
          'name' => 'Brazilian Market on WooCommerce',
          'url' => $ecfb_url,
          'version' => [
            'validation' => '>=',
            'number' => '3.5'
          ]
        ]
      ]
    ];

    self::is_wc_subscriptions_active();

    $errors = [];

    foreach ($required_plugins as $plugin) {
      if (self::is_plugin_active($plugin) == false) {
        $name = $plugin['plugin']['name'];
        $number = $plugin['plugin']['version']['number'];
        $url = $plugin['plugin']['url'];
        $notice = function () use ($name, $number, $url) {
          self::missing_notice($name, $number, $url);
        };
        add_action(
          'admin_notices',
          $notice
        );

        array_push($errors, $plugin);
      }

      if (self::verify_plugin_version($plugin) == false) {
        array_push($errors, $plugin);
      }
    }

    if(!empty($errors)) {
      return false;
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
    include plugin_dir_path(VINDI_SRC) . 'src/views/missing-dependency.php';
  }

  /**
   * Generate critical dependency notice content
   *
   * @param string $name Dependency name
   * @param string $version Dependency version
   *
   * @return  string
   */
  public static function critical_dependency_missing_notice($name, $version)
  {
    include plugin_dir_path(VINDI_SRC) . 'src/views/missing-critical-dependency.php';
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
    if(in_array($plugin['path'], self::$active_plugins) && is_plugin_active($plugin['path'])) {
      return true;
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
      $name = $plugin['plugin']['name'];
      $number = $version_match['number'];
      $url = $plugin['plugin']['url'];
      $notice = function () use ($name, $number, $url) {
        self::missing_notice($name, $number, $url);
      };
      add_action(
        'admin_notices',
        $notice
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
