<?php

namespace BeansWoo\Admin\Connector;

use BeansWoo\Helper;

defined('ABSPATH') or die;

class UltimateConnector {

	static public $app_name = 'ultimate';
	static public $app_info;
	static public $card ;

    static public $errors = array();
    static public $messages = array();

    public static function init(){
        self::$card = Helper::getBeansObject(self::$app_name, 'card');

        self::$app_info = Helper::getApps()[self::$app_name];

        add_action('admin_init', array(__CLASS__, 'install_default_assets'));
    }

    protected static function _install_assets($app_name = null) {
        if (!in_array($app_name, ['liana', 'bamboo'])){
            return false;
        }
        $name = $app_name;
        // Install Page
        $page_infos = Helper::getPages()[$name];

        if ( ! get_post( Helper::getConfig( $name . '_page' ) ) ) {
            require_once( WP_PLUGIN_DIR . '/woocommerce/includes/admin/wc-admin-functions.php' );
            $page_id = wc_create_page(
                $page_infos['slug'],
                $page_infos['option'],
                $page_infos['page_name'],
                $page_infos['shortcode'], 0
            );
            Helper::setConfig( $name . '_page', $page_id );
        }
        return true;
    }

    public static function render_settings_page() {

        if ( isset( $_GET['card'] ) && isset( $_GET['token'] ) ) {
            if ( self::_process_setup() ) {
                return wp_redirect( BEANS_WOO_MENU_LINK  );
            }
        }

        if ( isset( $_GET['reset_beans'] ) ) {
            if ( Helper::resetSetup(self::$app_name) ) {
                self::_uninstall_assets();
                return wp_redirect(BEANS_WOO_MENU_LINK);
            }
        }

        self::_render_notices();

        if ( Helper::isSetup() && Helper::isSetupApp(static::$app_name) ) {
            self::update_installed_app();
            return include( dirname( __FILE__ , 2) . '/views/html-info.php' );
        }

        return include( dirname( __FILE__,2) . '/views/html-connect.php' );
    }

    protected static function _process_setup() {
        Helper::log( print_r( $_GET, true ) );

        $card  = $_GET['card'];
        $token = $_GET['token'];

        Helper::$key = $card;

        try {
            $integration_key = Helper::API()->get( '/core/auth/integration_key/' . $token );
        } catch ( \Beans\Error\BaseError  $e ) {
            self::$errors[] = 'Connecting to Beans failed with message: ' . $e->getMessage();
            Helper::log( 'Connecting failed: ' . $e->getMessage() );

            return null;
        }

        Helper::setConfig( 'key', $integration_key['id'] );
        Helper::setConfig( 'card', $integration_key['card']['id'] );
        Helper::setConfig( 'secret', $integration_key['secret'] );
        Helper::setInstalledApp(self::$app_name);
        return true;
    }

    public static function _render_notices() {
        if ( self::$errors || self::$messages ) {
            ?>
            <div class="<?php echo empty(self::$errors)? "updated" : "error"; ?> ">
                <?php if ( self::$errors ) : ?>
                    <ul>
                        <?php foreach ( self::$errors as $error ) {
                            echo "<li>$error</li>";
                        } ?>
                    </ul>
                <?php else : ?>
                    <ul>
                        <?php foreach ( self::$messages as $message ) {
                            echo "<li>$message</li>";
                        } ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    public static function admin_notice() {
        $user_id = get_current_user_id();
        if ( get_user_meta( $user_id,   'beans_'. static::$app_name . '_notice_dismissed' ) ){
            return;
        }

        if (! Helper::isSetup() || ! Helper::isSetupApp(static::$app_name)) {
            echo '<div class="notice notice-error " style="margin-left: auto"><div style="margin: 10px auto;"> Beans: '
                . __(  "Beans Ultimate is not properly setup.", 'beans-woo' ) .
                ' <a href="'. BEANS_WOO_MENU_LINK . '">' . __( 'Set up', 'beans-woo' ) . '</a>
                 <a style="float:right; text-decoration: none;" href="?beans_'. static::$app_name .'_notice_dismissed">
                    x
                 </a>' . '</div></div>';
        }
    }

    public static function notice_dismissed() {
        $user_id = get_current_user_id();
        if ( isset( $_GET['beans_'. static::$app_name .'_notice_dismissed'] ) ){
            add_user_meta(
                    $user_id, 'beans_'. static::$app_name .'_notice_dismissed', 'true', true
            );
            $location = $_SERVER['HTTP_REFERER'];
            wp_safe_redirect($location);
        }
    }

    protected static function _uninstall_assets()
    {
        delete_option(Helper::CONFIG_NAME);
    }

    public static function update_installed_app(){
        foreach (self::$card['apps'] as $app => $status){
            $app = strtolower($app);
            if( $status['is_installed'] ){
                self::_install_assets($app);
                Helper::setInstalledApp($app);
            }
        }
    }

    public static function install_default_assets(){
        self::_install_assets('liana');
        self::_install_assets('bamboo');
    }
}