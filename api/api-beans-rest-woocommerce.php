<?php


namespace BeansWoo\API;

defined('ABSPATH') or die;

use BeansWoo\Helper;

class BeansRestWoocommerce
{

    public static function init()
    {
        add_filter('woocommerce_rest_prepare_system_status', array(__CLASS__, 'add_site_pages_infos'), 90, 2);

        if (Helper::isSetup()) {
            add_filter('woocommerce_webhook_deliver_async', array(__CLASS__, 'async_webhook'), 10, 3);
            add_filter('woocommerce_rest_prepare_customer', array(__CLASS__, 'add_beans_app_activated'), 90, 1);
            add_filter('woocommerce_rest_prepare_product_object', array(__CLASS__, 'add_beans_app_activated'), 90, 1);
            add_filter('woocommerce_rest_prepare_shop_order_object', array(__CLASS__, 'add_beans_app_activated'), 90, 1);
        }

    }

    public static function async_webhook($true, $instance, $arg)
    {
        $topics = [
            'order.created',
            'customer.created',
        ];
        if (in_array($instance->get_topic(), $topics)) {
            return false;
        }
        return true;
    }

    public static function add_beans_app_activated($response)
    {
        $apps = Helper::getApps();
        $installed_apps = [];

        foreach ($apps as $app_name => $app_info) {
            if (Helper::isSetupApp($app_name)) {
                $installed_apps[] = $app_name;
            }
        }
        $response->data["beans_apps"] = $installed_apps;

        return $response;
    }

    public static function add_site_pages_infos($response, $system_status)
    {
        $pages = [
            get_option('woocommerce_myaccount_page_id') => array(
                'path' => wc_get_page_permalink('myaccount'),
                'type' => 'login'
            ),
            get_option('woocommerce_cart_page_id') => array(
                'path' => wc_get_cart_url(),
                'type' => 'cart',
            ),
            get_option('woocommerce_shop_page_id') => array(
                'path' => wc_get_page_permalink('shop'),
                'type' => 'product',
            ),
            get_option('woocommerce_checkout_page_id') => array(
                'path' => wc_get_checkout_url(),
                'type' => 'checkout'
            ),
        ];

        foreach ($pages as &$link) {
            $link['path'] = str_replace(home_url(), '', $link['path']);
        }

        if (!isset($response->data['pages'])) {
            $response->data['pages'] = [];
        }

        foreach ($response->data['pages'] as &$page) {
            if (isset($pages[$page['page_id']])) {
                $page = array_merge($page, $pages[$page['page_id']]);
            }
        }

        $response->data['pages'] = array_merge($response->data['pages'], self::get_beans_pages(), array(
                array(
                    'page_name' => 'Thank you',
                    'path' => str_replace(home_url(), '',
                        wc_get_endpoint_url(
                            get_option('woocommerce_checkout_order_received_endpoint'), '', wc_get_checkout_url()
                        )
                    ),
                    'type' => 'thank_you'
                ))
        );

        return $response;
    }

    public static function get_beans_pages()
    {
        $pages_output = [];

        foreach (Helper::getPages() as $app_name => $values) {

            $page_id = $values['page_id'];
            $page_exists = false;
            $page_visible = false;

            $page_set = true;
            if (get_post($page_id)) {
                $page_exists = true;
            }
            if ('publish' === get_post_status($page_id)) {
                $page_visible = true;
            }

            $pages_output[] = array(
                'page_id' => $page_id,
                'page_set' => $page_set,
                'type' => $values['type'],
                'shortcode_present' => True,
                'shortcode_required' => True,
                'page_exists' => $page_exists,
                'page_visible' => $page_visible,
                'page_name' => $values['page_name'],
                'shortcode' => $values['shortcode'],
                'path' => str_replace(home_url(), '', get_permalink(Helper::getConfig($app_name . '_page'))),
            );
        }

        return $pages_output;
    }
}
