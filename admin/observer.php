<?php

namespace BeansWoo\Admin;

defined('ABSPATH') or die;

use BeansWoo\Helper;

class Observer
{

    public static $submenu_pages = [];

    public static function init()
    {

        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_style'));
        add_action("admin_init", array(__CLASS__, "setting_options"));

        add_action('admin_notices', array('\BeansWoo\Admin\Connector\UltimateConnector', 'admin_notice'));
        add_action('admin_init', array('\BeansWoo\Admin\Connector\UltimateConnector', 'notice_dismissed'));

        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_init', array(__CLASS__, 'admin_is_curl_notice'), 0, 100);

    }

    public static function admin_style()
    {
        wp_enqueue_style('admin-styles', plugins_url('assets/css/beans-admin.css',
            BEANS_PLUGIN_FILENAME));
    }

    public static function setting_options()
    {
        add_settings_section("beans-section", "", null, "beans-woo");
        add_settings_field(
            "beans-liana-display-redemption-checkout",
            "Redemption on checkout",
            array(__CLASS__, "display_redeem_checkbox_setting"),
            "beans-woo", "beans-section"
        );
        register_setting("beans-section", "beans-liana-display-redemption-checkout");
    }

    public static function display_redeem_checkbox_setting()
    {
        ?>
        <!-- Here we are comparing stored value with 1. Stored value is 1 if user checks the checkbox otherwise empty string. -->
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
        if (current_user_can('manage_options')) {
            add_menu_page(
                'Beans',
                'Beans',
                'manage_options',
                BEANS_WOO_BASE_MENU_SLUG,
                array('\BeansWoo\Admin\Connector\UltimateConnector', 'render_settings_page'),
                plugins_url('/assets/img/beans-wordpress-icon.svg', BEANS_PLUGIN_FILENAME),
                56
            );
        }
    }

    public static function admin_is_curl_notice()
    {
        $text = "cURL is not installed. Please install and activate, otherwise, the Beans program may not work.";

        if (! Helper::isCURL()) {
            echo '<div class="notice notice-warning " style="margin-left: auto"><div style="margin: 10px auto;"> Beans: ' .
                __($text, 'beans-woo') .
                '</div></div>';
        }
    }

}
