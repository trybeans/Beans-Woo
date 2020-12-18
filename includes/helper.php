<?php

namespace BeansWoo;

defined('ABSPATH') or die;

use Beans\Beans;
use Beans\Error\BaseError;

class Helper {
    const CONFIG_NAME = 'beans-config-3';

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
                'title' => 'Make your customers addicted to your shop',
                'role' => 'Loyalty Program',
            ),

            'bamboo' => array(
                'title' => 'Turn your customers into advocates of your brand',
                'role' => 'Referral Program',
            ),

            'foxx' => array(
                'title' => 'Super-targeted automated emails that drive sales',
                'role' => 'Email Automation',
            ),

            'poppy' => array(
                'title' => 'Get customers to take actions when they are most likely to convert',
                'role' => 'Smart Popups'
            ),

            'snow' => array(
	            'title' => 'Communicate with customers without disrupting their journey',
                'role' => 'Notification Widget'
            ),

            'lotus' => array(
                'title' => 'Save time managing social media for your shop.',
                'role' => 'Social Media Automation',
            ),

            'arrow' => array(
                'title' => 'Know your customer.',
                'role' => 'Social Connect',
            ),

            'ultimate' => array(
                'title' => 'Connect your shop to get started',
                'role' => '',
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
        return Helper::getConfig( 'key' ) && Helper::getConfig( 'card' ) && Helper::getConfig( 'secret' );
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
			self::setConfig('apps', array());
			self::$cards = array();
		}

        return true;
    }

    public static function isSetupApp( $app_name){
        $apps = self::getConfig('apps');
        if(! $apps){
            $apps = array();
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

    public static function getBeansObject($appName, $objectName) {
        $object = $appName."_".$objectName;

        $beans_object = get_transient("beans_$object");

        $beans_object = $beans_object ? $beans_object : array();

        if ( isset($beans_object[$object]) ){
            return $beans_object[$object];
        }

        if ( self::isSetup() && self::isSetupApp($appName)) {
            try {
                $beans_object[$object] = self::API()->get( "${appName}/${objectName}/current" );
                set_transient("beans_$object", $beans_object, 10);
            } catch ( BaseError $e ) {
                self::log( 'Unable to get card: ' . $e->getMessage() );
            }
        }

        return isset($beans_object[$object]) ? $beans_object[$object] : null;
    }

    public static function getCart() {
        global $woocommerce;

        if ( ! empty( $woocommerce->cart ) && empty( $woocommerce->cart->cart_contents ) ) {
            $woocommerce->cart->calculate_totals();
        }

        return $woocommerce->cart;
    }

	public static function setInstalledApp($app_name){
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
            wc_get_cart_url() => 'cart',
            wc_get_checkout_url() => 'cart',
            wc_get_page_permalink('shop') => 'product',
            wc_get_page_permalink( 'myaccount' ) => 'login',
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
        }catch (BaseError $e) {}
    }

    public static  function isCURL(){
        return function_exists('curl_version');
    }

    public static function replaceTags($string, $tags, $force_lower = false){
        return preg_replace_callback('/\\{([^{}]+)\\}/',
            function($matches) use ($force_lower, $tags)
            {
                $key = $force_lower ? strtolower($matches[1]) : $matches[1];
                return array_key_exists($key, $tags) ? $tags[$key] : '';
            }
            , $string
        );
    }
}
