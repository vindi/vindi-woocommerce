<?php

namespace VindiPaymentGateways;

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
        self::$active_plugins = self::get_active_plugins();
    }

    private static function get_active_plugins()
    {
        if (!function_exists('get_plugin_data')) {
            return array();
        }

        $active_plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $network_activated_plugins = array_keys(
                get_site_option('active_sitewide_plugins', array())
            );

            $active_plugins = array_merge($active_plugins, $network_activated_plugins);
        }

        $active_plugins_data = array();

        foreach ($active_plugins as $plugin) {
            $data                  = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $active_plugins_data[] = self::format_plugin_data($plugin, $data);
        }

        return $active_plugins_data;
    }

    private static function format_plugin_data($plugin, $data)
    {
        return array(
            'plugin'            => $plugin,
            'name'              => $data['Name'],
            'version'           => $data['Version'],
            'url'               => $data['PluginURI'],
            'author_name'       => $data['AuthorName'],
            'author_url'        => esc_url_raw($data['AuthorURI']),
            'network_activated' => $data['Network'],
        );
    }

    public static function is_wc_memberships_active()
    {
        $wc_memberships = 'woocommerce-memberships/woocommerce-memberships.php';
        if (is_plugin_active($wc_memberships)) {
            return true;
        }
        return false;
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
                'name'    => 'PHP',
                'version' => [
                    'validation' => '>=',
                    'number'     => VINDI_MININUM_PHP_VERSION
                ]
            ],
            [
                'name'    => 'WordPress',
                'version' => [
                    'validation' => '>=',
                    'number'     => VINDI_MININUM_WP_VERSION
                ]
            ]
        ];

        $errors = [];

        foreach ($critical_dependencies as $dependency) {
            $version = $dependency['version'];

            if (!version_compare(PHP_VERSION, $version['number'], $version['validation'])) {
                $name   = $dependency['name'];
                $number = $version['number'];
                $notice = function () use ($name, $number) {
                    self::critical_dependency_missing_notice($name, $number);
                };

                add_action('admin_notices', $notice);
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

        $woocommerce_url = self::woocommerce_url();
        $ecfb_url = self::ecfb_url();

        $required_plugins = [
            [
                'path'    => 'woocommerce/woocommerce.php',
                'class'   => 'WooCommerce',
                'name'    => 'WooCommerce',
                'url'     =>  $woocommerce_url,
                'version' => [
                     'validation' => '>=',
                     'number' => '3.0'
                ]
            ],
            [
                'path'    =>
                    'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php',
                'class'   => 'Extra_Checkout_Fields_For_Brazil',
                'name'    => 'Brazilian Market on WooCommerce',
                'url'     => $ecfb_url,
                'version' => [
                    'validation' => '>=',
                    'number' => '3.5'
                ]
            ],
            [
                'path'    => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
                'class'   => 'WC_Subscriptions',
                'name'    => 'WooCommerce Subscription',
                'url'     => 'http://www.woothemes.com/products/woocommerce-subscriptions/',
                'version' => [
                    'validation' => '>=',
                    'number' => '2.6.1'
                ]
            ]
        ];

        return self::check_plugin_dependencies($required_plugins);
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
        if (!is_admin() || !is_user_logged_in()) {
            return;
        }

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

    private static function check_plugin_dependencies($required_plugins)
    {
        $checked = true;

        foreach ($required_plugins as $plugin) {
            $search = self::search_plugin($plugin, self::$active_plugins);

            if ($search &&
                version_compare(
                    $search['version'],
                    $plugin['version']['number'],
                    $plugin['version']['validation']
                )) {
                continue;
            }

            self::missing_notice(
                $plugin['name'],
                $plugin['version']['number'],
                $plugin['url']
            );

            $checked = false;
        }

        return $checked;
    }

    private static function search_plugin($required, $array)
    {
        foreach ($array as $val) {
            if ($val['plugin'] === $required['path'] && class_exists($required['class'])) {
                return $val;
            }
        }
        return null;
    }

    private static function woocommerce_url()
    {
        if (current_user_can('install_plugins')) {
            return wp_nonce_url(
                self_admin_url('update.php?action=install-plugin&plugin=woocommerce'),
                'install-plugin_woocommerce'
            );
        }

        return 'https://wordpress.org/extend/plugins/woocommerce/';
    }

    private static function ecfb_url()
    {
        if (current_user_can('install_plugins')) {
            return wp_nonce_url(
                self_admin_url(
                    'update.php?action=install-plugin&plugin=' .
                    'woocommerce-extra-checkout-fields-for-brazil'
                ),
                'install-plugin_woocommerce-extra-checkout-fields-for-brazil'
            );
        }

        return 'https://wordpress.org/extend/plugins/woocommerce-extra-checkout-fields-for-brazil/';
    }
}
