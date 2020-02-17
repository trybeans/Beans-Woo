<?php

namespace BeansWoo;

use Beans\Beans;

class Helper {
    const CONFIG_NAME = 'beans-config-3';

    const BEANS_ULTIMATE_DISMISSED = 'beans_ultimate_dismissed';

    const BASE_LINK = 'admin.php?page=';

    private static $cards = array();
    public static $key = null;

    public static function getDomain( $sub ) {
        $key     = "BEANS_DOMAIN_$sub";
        $domains = array(
            'NAME' => 'trybeans.com',
            'API'     => 'api-3.trybeans.com',
            'CONNECT' => 'connect.trybeans.com',
            'WWW'     => 'www.trybeans.com',
            'STATIC' => 'trybeans.s3.amazonaws.com'
        );
        $val     = getenv( $key );

        return empty( $val ) ? $domains[ $sub ] : getenv( $key );
    }

    public static function getApps() {
        return array(
            'liana' => array(
                'name' => 'Liana',
                'title' => 'Make your customers addicted to your shop',
                'description' =>'Get your customers to place a second order, a third, a forth and more.',
	            'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG,
            ),

            'bamboo' => array(
                'name' => 'Bamboo',
                'title' => 'Turn your customers into advocates of your brand',
                'description' => 'Let your customers grow your business by referring you to their friends.',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-bamboo',
            ),

            'foxx' => array(
                'name' => 'Foxx',
                'title' => 'Super-targeted automated emails that drive sales',
                'description' => 'Reach out to customers with highly relevant offers at the moment they are most likely to shop.',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-foxx',
            ),

            'poppy' => array(
                'name' => 'Poppy',
                'title' => 'Get customers to take actions when they are most likely to convert',
                'description' => 'Display the right popup at the right time to the right customer.',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-poppy',
            ),

            'snow' => array(
            	'name' => 'Snow',
	            'title' => 'Communicate with customers without disrupting their journey',
	            'description' => 'Automatically let customers know about new products and promotions in your shop.',
	            'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-snow',
            ),

            'lotus' => array(
                'name' => 'Lotus',
                'title' => 'Save time managing social media for your shop.',
                'description' => 'Automatically let customers know about new products and promotions in your shop.',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-lotus',
            ),

            'arrow' => array(
                'name' => 'Arrow',
                'title' => 'Know your customer.',
                'description' => '',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG . '-arrow',
            ),

            'ultimate' => array(
                'name' => 'Ultimate',
                'title' => 'Everything you need to sell more',
                'description' => '',
                'link' => self::BASE_LINK . BEANS_WOO_BASE_MENU_SLUG,
            )
        );
    }

    public static function API() {
        if ( ! self::$key ) {
            self::$key = self::getConfig( 'secret' );
        }
        $beans = new Beans(self::$key);

        $beans->endpoint = 'https://' . self::getDomain( 'API' ) . '/v3/';

        return $beans;
    }

    public static function getAccountData( $account, $k, $default = null ) {
        if ( isset( $account[ $k ] ) ) {
            echo "$k:'" . $account[ $k ] . "',";
        } else if ( $default !== null ) {
            echo "$k: '',";
        }
    }

    public static function getConfig( $key ) {
        $config = get_option( self::CONFIG_NAME );
        if ( isset( $config[ $key ] ) ) {
            return $config[ $key ];
        }

        return null;
    }

    public static function setConfig( $key, $value ) {
        $config         = get_option( self::CONFIG_NAME );
        $config[ $key ] = $value;
        update_option( self::CONFIG_NAME, $config );
    }

    public static function isSetup() {
        return Helper::getConfig( 'key' ) &&
               Helper::getConfig( 'card' ) &&
               Helper::getConfig( 'secret' );
    }

    public static function resetSetup($app_name) {
    	$apps_installed = self::getConfig('apps');

    	if( in_array($app_name, $apps_installed) ){
    		unset($apps_installed[ $app_name ]);
            $app_page = self::getConfig($app_name . '_page');
            if (!is_null($app_page)){
				wp_delete_post($app_page, true);
				self::setConfig($app_name . '_page', null);
			}
            self::setConfig('apps', $apps_installed);
    	}

    	if (empty($apps_installed)){
			self::setConfig( 'key', null );
			self::setConfig( 'card', null );
			self::setConfig( 'secret', null );
			self::setConfig('apps', []);
			self::$cards = array();
		}

        return true;
    }

    public static function isSetupApp( $app_name){
        $apps = self::getConfig('apps');
        if(! $apps){
            $apps = [];
        }
        return in_array($app_name, $apps);
    }

    public static function log( $info ) {
        if ( file_exists( BEANS_INFO_LOG ) && filesize( BEANS_INFO_LOG ) > 100000 ) {
            unlink( BEANS_INFO_LOG );
        }

        if ( ! is_writable( BEANS_INFO_LOG ) ) {
            return false;
        }

        $log = date( 'Y-m-d H:i:s.uP' ) . " => " . $info . PHP_EOL;

        try {
            file_put_contents( BEANS_INFO_LOG, $log, FILE_APPEND );
        } catch ( \Exception $e ) {
            return false;
        }

        return true;
    }

    public static function getCard($app_name) {
        $beans_card = get_transient('beans_card');

        $beans_card = $beans_card ? $beans_card : [];

        if ( isset($beans_card[$app_name]) ){
            return $beans_card[$app_name];
        }

        if ( ! isset(self::$cards[$app_name]) && self::isSetup() && self::isSetupApp($app_name)) {
            try {
                $beans_card[$app_name] = self::API()->get( "${app_name}/card/current" );
                set_transient('beans_card', $beans_card, 2*60);
            } catch ( \Beans\Error\BaseError $e ) {
                self::log( 'Unable to get card: ' . $e->getMessage() );
            }
        }

        return isset($beans_card[$app_name]) ? $beans_card[$app_name] : null;
    }

    public static function getCart() {
        global $woocommerce;

        if ( ! empty( $woocommerce->cart ) && empty( $woocommerce->cart->cart_contents ) ) {
            $woocommerce->cart->calculate_totals();
        }

        return $woocommerce->cart;
    }

	public static function setAppInstalled($app_name){
		$config         = get_option( self::CONFIG_NAME );
		if (isset($config['apps'])){
			if( !in_array($app_name, $config['apps']) ){
				$config['apps'][ $app_name] =  $app_name;
			}
		}else{
			$config['apps'] = array($app_name => $app_name, );
		}
		update_option( self::CONFIG_NAME, $config );
	}

	public static function getPages(){
        return [
            'liana' => [
                'shortcode' => '[beans_page]',
                'page_id' => self::getConfig('liana_page'),
                'page_name' => 'Rewards Program',
                'option' => 'beans_page_id',
                'slug' => 'rewards-program',
                'type' => 'reward',
            ],
            'bamboo' => [
                'shortcode' => '[beans_referral_page]',
                'page_id' => self::getConfig('bamboo_page'),
                'page_name' => 'Referral Program',
                'option' => 'beans_referral_page_id',
                'slug' => 'referral-program',
                'type' => 'referral',
            ],

        ];
    }

    public static function getCurrentPage(){
        $pages = [
            get_permalink(get_option('woocommerce_myaccount_page_id')) => 'login',
            get_permalink(get_option('woocommerce_cart_page_id')) => 'cart',
            get_permalink(get_option('woocommerce_shop_page_id')) => 'product',
            get_permalink(get_option('woocommerce_checkout_page_id')) => 'cart',
            get_permalink(Helper::getConfig('liana_page')) => 'reward',
            get_permalink(Helper::getConfig('bamboo_page')) => 'referral',
        ];

        $current_page = esc_url(home_url($_SERVER['REQUEST_URI']));
        $current_page = explode("?", $current_page)[0];
        return isset($pages[$current_page]) ? $pages[$current_page] : '';
    }

    public static function postWebhookStatus($status){
        $args = [
          'status' => $status
        ];
        $headers =  array(
            'X-WC-Webhook-Source:'. home_url(),
        );

        try{
            self::API()->post('/radix/woocommerce/hook/shop/plugin_status', $args, $headers);
        }catch (\Beans\Error\BaseError $e) {}
    }

    public static  function isCURL(){
        return function_exists('curl_version');
    }

}
