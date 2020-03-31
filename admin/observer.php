<?php

namespace BeansWoo\Admin;

defined('ABSPATH') or die;

use BeansWoo\Admin\Connector\LianaConnector;
use BeansWoo\Admin\Connector\UltimateConnector;
use BeansWoo\Helper;

class Observer
{

    public static function init()
    {

        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_style'));
        add_action("admin_init", array(__CLASS__, "setting_options"));

        add_action('admin_notices', array('\BeansWoo\Admin\Connector\UltimateConnector', 'admin_notice'));
        add_action('admin_init', array('\BeansWoo\Admin\Connector\UltimateConnector', 'notice_dismissed'));

        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_init', array(__CLASS__, 'admin_is_curl_notice'), 0, 100);

    }

    public static function plugin_row_meta($links, $file)
    {
        if ($file == BEANS_PLUGIN_FILENAME) {

            $row_meta = array(
                'help' => '<a href="http://help.trybeans.com/" title="Help">Help Center</a>',
                'support' => '<a href="mailto:hello@trybeans.com" title="Support">Contact Support</a>',
            );

            return array_merge($links, $row_meta);
        }

        return (array)$links;
    }

    public static function admin_style()
    {
        wp_enqueue_style('admin-styles', plugins_url('assets/css/beans-admin.css', BEANS_PLUGIN_FILENAME));
    }

    public static function setting_options()
    {
        add_settings_section("beans-section", "", null, "beans-woo");
        add_settings_field(
            "beans-liana-display-redemption-checkout",
            "Redemption on checkout",
            array(__CLASS__, "redemption_button_checkbox_display"),
            "beans-woo", "beans-section"
        );
        register_setting("beans-section", "beans-liana-display-redemption-checkout");
    }

    public static function redemption_button_checkbox_display()
    {
        ?>
        <div>
            <input type="checkbox" id="beans-liana-display-redemption-checkout"
                   name="beans-liana-display-redemption-checkout"
                   value="1" <?php checked(1, get_option('beans-liana-display-redemption-checkout'), true); ?> />
            <label for="beans-liana-display-redemption-checkout">Display redemption on checkout page</label>
        </div>
        <?php
    }

    public static function admin_menu()
    {
        $menu = array([
            'page_title' => ucfirst(UltimateConnector::$app_name),
            'menu_title' => ucfirst(UltimateConnector::$app_name),
            'menu_slug' => BEANS_WOO_BASE_MENU_SLUG,
            'capability' => 'manage_options',
            'callback' => '',
            'render' => ['\BeansWoo\Admin\Connector\UltimateConnector', 'render_settings_page']
        ]);

        $menu[0]['parent_slug'] = $menu[0]['menu_slug'];

        if (current_user_can('manage_options')) {
            add_menu_page(
                'Beans',
                'Beans',
                'manage_options',
                $menu[0]['menu_slug'],
                $menu[0]['render'],
                plugins_url('/assets/img/beans-wordpress-icon.svg', BEANS_PLUGIN_FILENAME),
                56);
        }
    }

    public static function admin_is_curl_notice()
    {
        $text = "cURL is not installed. Please install and activate, otherwise, the Beans program may not work.";

        if (!Helper::isCURL()) {
            echo '<div class="notice notice-warning " style="margin-left: auto"><div style="margin: 10px auto;"> Beans: ' .
                __($text, 'beans-woo') .
                '</div></div>';
        }
    }

}
